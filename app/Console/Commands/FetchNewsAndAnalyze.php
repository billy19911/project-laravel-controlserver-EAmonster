<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\EconomicNews;
use App\Services\EconomicCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchNewsAndAnalyze extends Command
{
    protected $signature = 'news:fetch-analyze';

    protected $description = 'Fetch USD economic calendar and generate AI impact analysis for XAUUSD';

    public function handle(): int
    {
        $windowStart = Carbon::now()->startOfDay();
        $windowEnd = Carbon::now()->addDays(10)->endOfDay();

        $payload = $this->fetchCalendarPayload();
        if ($payload === null) {
            $this->warn('Failed to fetch external calendar feed and fallback source returned no rows.');
            return self::SUCCESS;
        }

        $this->info('Fetched ' . count($payload) . ' raw calendar rows from source(s).');

        $rows = collect($payload)
            ->filter(fn (array $row): bool => strtoupper((string) ($row['country'] ?? '')) === 'USD')
            ->filter(function (array $row): bool {
                $impact = strtoupper((string) ($row['impact'] ?? ''));
                return in_array($impact, ['HIGH', 'MEDIUM', 'LOW'], true);
            })
            ->map(function (array $row): array {
                $eventAt = $this->parseEventAt($row);
                return [
                    'event_at' => $eventAt,
                    'currency' => strtoupper((string) ($row['country'] ?? 'USD')),
                    'impact' => strtoupper((string) ($row['impact'] ?? 'MEDIUM')),
                    'title' => (string) ($row['title'] ?? $row['event'] ?? 'USD Event'),
                    'raw_payload' => $row,
                ];
            })
            ->filter(fn (array $row): bool => $row['event_at'] !== null)
            ->filter(fn (array $row): bool => $row['event_at']->betweenIncluded($windowStart, $windowEnd))
            ->values();

        if ($rows->isEmpty()) {
            $this->warn('No USD low/medium/high impact news found in the next 10-day window.');
            return self::SUCCESS;
        }

        foreach ($rows as $event) {
            [$analysis, $verdict] = $this->buildEventAnalysisAndVerdict($event);

            EconomicNews::query()->updateOrCreate(
                [
                    'event_at' => $event['event_at'],
                    'currency' => $event['currency'],
                    'title' => $event['title'],
                ],
                [
                    'impact' => $event['impact'],
                    'ai_analysis' => $analysis,
                    'ai_verdict' => $verdict,
                    'raw_payload' => $event['raw_payload'],
                ]
            );
        }

        $this->info('Economic news and AI analysis updated successfully. Stored ' . $rows->count() . ' rows.');
        return self::SUCCESS;
    }

    private function buildEventAnalysisAndVerdict(array $event): array
    {
        $title = (string) ($event['title'] ?? 'USD Event');
        $impact = strtoupper((string) ($event['impact'] ?? 'MEDIUM'));
        $raw = (array) ($event['raw_payload'] ?? []);

        $actualRaw = $this->extractMetric($raw, ['actual', 'Actual', 'actual_value']);
        $forecastRaw = $this->extractMetric($raw, ['forecast', 'Forecast', 'consensus', 'estimate']);
        $previousRaw = $this->extractMetric($raw, ['previous', 'Previous', 'prior']);

        $actual = $this->metricToFloat($actualRaw);
        $forecast = $this->metricToFloat($forecastRaw);

        $hawkish = ['CPI', 'NFP', 'PAYROLLS', 'PCE', 'RETAIL SALES', 'INFLATION'];
        $dovish = ['UNEMPLOYMENT', 'JOBLESS CLAIMS', 'PMI', 'CONSUMER CONFIDENCE'];
        $upper = strtoupper($title);

        $direction = 0;
        foreach ($hawkish as $keyword) {
            if (str_contains($upper, $keyword)) {
                $direction = -1;
                break;
            }
        }
        if ($direction === 0) {
            foreach ($dovish as $keyword) {
                if (str_contains($upper, $keyword)) {
                    $direction = 1;
                    break;
                }
            }
        }

        $isPolicyEvent = str_contains($upper, 'FOMC') || str_contains($upper, 'MINUTES') || str_contains($upper, 'SPEECH');
        $isLaborClaim = str_contains($upper, 'CLAIMS');

        $surprise = null;
        if ($actual !== null && $forecast !== null) {
            $surprise = $actual - $forecast;
        }

        $score = 0;
        if ($direction !== 0) {
            $score += $direction;
        }
        if ($surprise !== null && $direction !== 0) {
            if ($isLaborClaim) {
                $score += $surprise > 0 ? 1 : ($surprise < 0 ? -1 : 0);
            } else {
                $score += $surprise > 0 ? -1 : ($surprise < 0 ? 1 : 0);
            }
        }

        $verdict = 'GOLD NEUTRAL';
        if ($score >= 1) {
            $verdict = 'GOLD BULLISH';
        } elseif ($score <= -1) {
            $verdict = 'GOLD BEARISH';
        }
        if ($isPolicyEvent && $surprise === null) {
            $verdict = 'GOLD NEUTRAL';
        }

        $surpriseText = 'actual belum rilis';
        if ($surprise !== null) {
            $surpriseText = $surprise > 0
                ? 'actual di atas forecast'
                : ($surprise < 0 ? 'actual di bawah forecast' : 'actual sejalan forecast');
        }

        $risk = $impact === 'HIGH' ? 'tinggi' : 'menengah';
        $focus = $isPolicyEvent
            ? 'Fokus pada nada kebijakan (hawkish/dovish) serta perubahan ekspektasi suku bunga.'
            : 'Fokus pada deviasi actual-vs-forecast dan konfirmasi respons awal USD Index.';
        $biasLine = $verdict === 'GOLD NEUTRAL'
            ? 'Bias awal netral dengan potensi spike dua arah.'
            : ('Bias awal ' . $verdict . ' selama momentum data masih valid.');

        $analysis = sprintf(
            'Event %s (%s). Actual: %s | Forecast: %s | Previous: %s. Pembacaan %s. %s Risiko volatilitas %s. %s',
            $title,
            $impact,
            $actualRaw,
            $forecastRaw,
            $previousRaw,
            $surpriseText,
            $biasLine,
            $risk,
            $focus
        );

        return [Str::limit($analysis, 600, ''), $verdict];
    }

    private function normalizeMetric(string $value): string
    {
        $trimmed = trim($value);
        return $trimmed === '' ? 'Menunggu rilis' : $trimmed;
    }

    private function extractMetric(array $raw, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }

            $value = trim((string) $raw[$key]);
            if ($value !== '' && strtolower($value) !== 'null' && strtoupper($value) !== 'N/A') {
                return $value;
            }
        }

        return 'Menunggu rilis';
    }

    private function metricToFloat(string $value): ?float
    {
        $sanitized = trim($value);
        $sanitized = str_replace([',', '%'], ['', ''], $sanitized);
        if ($sanitized === '' || str_contains(strtolower($sanitized), 'menunggu')) {
            return null;
        }

        if (!is_numeric($sanitized)) {
            return null;
        }

        return (float) $sanitized;
    }

    private function parseEventAt(array $row): ?Carbon
    {
        $dateCandidates = [
            (string) ($row['date'] ?? ''),
            (string) ($row['event_at'] ?? ''),
            (string) ($row['time_formatted'] ?? ''),
        ];

        foreach ($dateCandidates as $date) {
            if (trim($date) === '') {
                continue;
            }

            try {
                return Carbon::parse($date);
            } catch (\Throwable) {
                // Try next format candidate.
            }
        }

        $timestamp = (int) ($row['time'] ?? 0);
        if ($timestamp > 0) {
            try {
                return Carbon::createFromTimestamp($timestamp);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function analyzeWithAiOrFallback(string $eventSummary): array
    {
        $key = (string) env('OPENAI_API_KEY', '');
        $model = (string) env('OPENAI_MODEL', 'gpt-4o-mini');

        if ($key !== '') {
            $prompt = "Anda analis makro XAUUSD profesional. Berdasarkan jadwal rilis ekonomi USD berikut, berikan analisis otomatis yang singkat (maksimal 3 kalimat), fokus pada arah dominan emas dan level risiko volatilitas intraday. Berikan vonis tegas: GOLD BULLISH atau GOLD BEARISH. Balas JSON murni dengan format: {\"analysis\":\"...\",\"verdict\":\"GOLD BULLISH|GOLD BEARISH\"}.\n\nData:\n" . $eventSummary;

            $response = Http::timeout(30)
                ->withToken($key)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.2,
                    'max_tokens' => 180,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Anda analis makro profesional untuk XAUUSD.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->ok()) {
                $content = (string) data_get($response->json(), 'choices.0.message.content', '');
                $decoded = json_decode($this->extractJson($content), true);

                if (is_array($decoded)) {
                    $analysis = (string) ($decoded['analysis'] ?? 'Analisis tidak tersedia.');
                    $verdict = strtoupper((string) ($decoded['verdict'] ?? 'GOLD BULLISH'));
                    if (!in_array($verdict, ['GOLD BULLISH', 'GOLD BEARISH'], true)) {
                        $verdict = 'GOLD BULLISH';
                    }

                    return [Str::limit($analysis, 600, ''), $verdict];
                }
            }
        }

        return $this->fallbackAnalysis($eventSummary);
    }

    private function extractJson(string $text): string
    {
        $text = trim($text);
        if (str_starts_with($text, '{') && str_ends_with($text, '}')) {
            return $text;
        }

        if (preg_match('/\{.*\}/s', $text, $matches) === 1) {
            return $matches[0];
        }

        return json_encode([
            'analysis' => 'Rilis data USD hari ini berpotensi meningkatkan volatilitas XAUUSD. Reaksi harga diperkirakan cepat di sekitar jam rilis. Gunakan manajemen risiko ketat pada periode ini.',
            'verdict' => 'GOLD BULLISH',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function fallbackAnalysis(string $eventSummary): array
    {
        $hawkish = ['CPI', 'NFP', 'PAYROLLS', 'PCE', 'RETAIL SALES'];
        $dovish = ['UNEMPLOYMENT', 'JOBLESS CLAIMS', 'PMI', 'CONSUMER CONFIDENCE'];

        $score = 0;
        $upper = strtoupper($eventSummary);
        foreach ($hawkish as $word) {
            if (str_contains($upper, $word)) {
                $score--;
            }
        }
        foreach ($dovish as $word) {
            if (str_contains($upper, $word)) {
                $score++;
            }
        }

        $verdict = $score >= 0 ? 'GOLD BULLISH' : 'GOLD BEARISH';
        $analysis = $verdict === 'GOLD BULLISH'
            ? 'Data USD hari ini cenderung membuka ruang pelemahan dolar sehingga emas berpeluang menguat. Volatilitas intraday diperkirakan meningkat di sekitar jam rilis. Fokus pada entry setelah konfirmasi arah untuk menghindari whipsaw.'
            : 'Data USD hari ini cenderung mendukung penguatan dolar sehingga emas berisiko tertekan. Volatilitas intraday diperkirakan meningkat di sekitar jam rilis. Fokus pada skenario sell setelah konfirmasi momentum untuk menjaga akurasi entry.';

        return [$analysis, $verdict];
    }

    private function fetchCalendarPayload(): ?array
    {
        $calendarService = app(EconomicCalendarService::class);
        $serviceRows = collect($calendarService->getHighImpactEvents())
            ->map(static function (array $item): array {
                return [
                    'date' => (string) ($item['time_formatted'] ?? ''),
                    'country' => (string) ($item['country'] ?? 'USD'),
                    'impact' => (string) ($item['importance'] ?? 'MEDIUM'),
                    'title' => (string) ($item['event'] ?? 'USD Event'),
                    'actual' => (string) ($item['actual'] ?? ''),
                    'forecast' => (string) ($item['forecast'] ?? ''),
                    'previous' => (string) ($item['previous'] ?? ''),
                    'time' => (int) ($item['time'] ?? 0),
                    'time_formatted' => (string) ($item['time_formatted'] ?? ''),
                ];
            })
            ->values()
            ->all();

        if ($serviceRows !== []) {
            return $serviceRows;
        }

        if ($calendarService->providerKey() !== 'forexfactory') {
            return null;
        }

        $urls = [
            'https://nfs.faireconomy.media/ff_calendar_thisweek.json',
            'https://nfs.faireconomy.media/ff_calendar_thismonth.json',
        ];
        $rows = [];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(25)
                    ->acceptJson()
                    ->withHeaders(['User-Agent' => 'EA-Monster-NewsFetcher/1.0'])
                    ->get($url);
                if ($response->ok() && is_array($response->json())) {
                    $rows = array_merge($rows, $response->json());
                    continue;
                }

                $response = Http::withoutVerifying()
                    ->timeout(25)
                    ->acceptJson()
                    ->withHeaders(['User-Agent' => 'EA-Monster-NewsFetcher/1.0'])
                    ->get($url);
                if ($response->ok() && is_array($response->json())) {
                    $rows = array_merge($rows, $response->json());
                    continue;
                }

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 25,
                        'header' => "User-Agent: EA-Monster-NewsFetcher/1.0\r\nAccept: application/json\r\n",
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);

                $raw = @file_get_contents($url, false, $context);
                if (!is_string($raw) || trim($raw) === '') {
                    continue;
                }

                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $rows = array_merge($rows, $decoded);
                }
            } catch (\Throwable $exception) {
                // Keep command output concise; runtime failure detail is not printed per-source.
            }
        }

        if ($rows !== []) {
            return $rows;
        }
        return null;
    }
}
