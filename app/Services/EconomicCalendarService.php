<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class EconomicCalendarService
{
    private const FINNHUB_ECONOMIC_PATH = '/calendar/economic';
    private const FOREXFACTORY_JSON_URLS = [
        'https://nfs.faireconomy.media/ff_calendar_thisweek.json',
        'https://nfs.faireconomy.media/ff_calendar_thismonth.json',
    ];
    private const FOREXFACTORY_HTML_URL = 'https://www.forexfactory.com/calendar.php';
    private const CACHE_KEY_PREFIX = 'economic_calendar_provider_';
    private const DEFAULT_CACHE_TTL = 1800;

    public function providerKey(): string
    {
        $provider = strtolower(trim((string) config('services.news.provider', '')));
        if (in_array($provider, ['finnhub', 'forexfactory'], true)) {
            return $provider;
        }

        $envProvider = strtolower(trim((string) env('NEWS_CALENDAR_PROVIDER', '')));
        if (in_array($envProvider, ['finnhub', 'forexfactory'], true)) {
            return $envProvider;
        }

        $finnhubKey = $this->resolveFinnhubApiKey();
        if ($finnhubKey !== '') {
            return 'finnhub';
        }

        return 'forexfactory';
    }

    public function providerLabel(): string
    {
        return $this->providerKey() === 'finnhub' ? 'Finnhub' : 'ForexFactory';
    }

    public function hasFinnhubApiKey(): bool
    {
        return $this->resolveFinnhubApiKey() !== '';
    }

    /**
     * Get important economic events from ForexFactory.
     * Includes HIGH and MEDIUM impact rows so the dashboard can show both.
     *
     * @return array Array of events with time, country, event, importance, forecast, previous
     */
    public function getHighImpactEvents(): array
    {
        $provider = $this->providerKey();

        return Cache::remember($this->cacheKey($provider), $this->cacheTtl(), function () use ($provider) {
            if ($provider === 'finnhub') {
                $finnhubRows = $this->fetchFinnhubRows();
                if (!empty($finnhubRows)) {
                    return $this->normalizeFinnhubRows($finnhubRows);
                }
            }

            return $this->fetchForexFactoryEvents();
        });
    }

    /**
     * Get next high-impact USD event
     * @return array|null Event data or null if none found
     */
    public function getNextHighImpactUsdEvent(): ?array
    {
        $events = $this->getHighImpactEvents();
        $now = Carbon::now()->timestamp;

        foreach ($events as $event) {
            if ($event['time'] > $now && strtoupper($event['country']) === 'USD') {
                return $event;
            }
        }

        return null;
    }

    /**
     * Parse ForexFactory HTML calendar.
     * Looks for rows with data-eventid and extracts HIGH/MEDIUM events.
     */
    private function parseCalendarHtml(string $html): array
    {
        $events = [];

        try {
            // Match calendar event rows - looking for data with event details
            // ForexFactory format: tr with data-eventid, containing time, country, event name, importance (star count)
            $pattern = '/data-eventid="(\d+)"[^>]*>.*?<td[^>]*class="[^"]*calendar__time[^"]*"[^>]*>([^<]+)<.*?<td[^>]*class="[^"]*calendar__currency[^"]*"[^>]*>([A-Z]{3})<.*?<td[^>]*class="[^"]*calendar__event[^"]*"[^>]*>([^<]+)<.*?<td[^>]*class="[^"]*calendar__impact[^"]*"[^>]*>([^<]*)/is';

            preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

            $now = Carbon::now('UTC');

            foreach ($matches as $match) {
                $eventId = $match[1] ?? '';
                $timeStr = trim($match[2] ?? '');
                $country = trim($match[3] ?? '');
                $eventName = trim($match[4] ?? '');
                $importance = trim($match[5] ?? '');

                $impactLevel = $this->determineImpactLevel($importance);

                // Skip if not at least medium importance.
                if ($impactLevel === null) {
                    continue;
                }

                // Parse time - ForexFactory shows time like "14:30" for today, or "Tomorrow 08:00", etc.
                $eventTime = $this->parseEventTime($timeStr, $now);
                if (!$eventTime) {
                    continue;
                }

                // Only future events
                if ($eventTime->isPast()) {
                    continue;
                }

                $events[] = [
                    'event_id' => $eventId,
                    'time' => $eventTime->timestamp,
                    'time_formatted' => $eventTime->toDateTimeString(),
                    'country' => $country,
                    'event' => $eventName,
                    'importance' => $impactLevel,
                    'forecast' => '',
                    'previous' => '',
                ];
            }
        } catch (\Exception $e) {
            \Log::warning('ForexFactory calendar parsing failed', [
                'error' => $e->getMessage(),
            ]);
        }

        // Sort by time
        usort($events, fn ($a, $b) => $a['time'] <=> $b['time']);

        return $events;
    }

    /**
     * Parse event time string from ForexFactory
     * Handles: "14:30", "Tomorrow 08:00", "Friday 10:00", etc.
     */
    private function parseEventTime(string $timeStr, Carbon $now): ?Carbon
    {
        try {
            $timeStr = strtolower(trim($timeStr));

            // Remove extra whitespace
            $timeStr = preg_replace('/\s+/', ' ', $timeStr);

            // Handle "HH:MM" format (today)
            if (preg_match('/^\d{1,2}:\d{2}$/', $timeStr)) {
                [$hour, $minute] = explode(':', $timeStr);
                $time = $now->copy()
                    ->setHour((int)$hour)
                    ->setMinute((int)$minute)
                    ->setSecond(0);

                // If time is in the past today, assume it's tomorrow
                if ($time->isPast()) {
                    $time->addDay();
                }

                return $time;
            }

            // Handle "tomorrow HH:MM"
            if (preg_match('/tomorrow\s+(\d{1,2}):(\d{2})/', $timeStr, $m)) {
                return $now->copy()
                    ->addDay()
                    ->setHour((int)$m[1])
                    ->setMinute((int)$m[2])
                    ->setSecond(0);
            }

            // Handle day names "monday HH:MM", "tuesday 10:00", etc.
            $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($dayNames as $dayName) {
                if (preg_match("/{$dayName}\s+(\d{1,2}):(\d{2})/", $timeStr, $m)) {
                    $daysAhead = array_search($dayName, $dayNames) - $now->dayOfWeekIso % 7;
                    if ($daysAhead <= 0) {
                        $daysAhead += 7;
                    }

                    return $now->copy()
                        ->addDays($daysAhead)
                        ->setHour((int)$m[1])
                        ->setMinute((int)$m[2])
                        ->setSecond(0);
                }
            }

            // Handle "MMM DD HH:MM" format
            if (preg_match('/([a-z]{3})\s+(\d{1,2})\s+(\d{1,2}):(\d{2})/', $timeStr, $m)) {
                try {
                    $time = Carbon::createFromFormat('M d H:i', "{$m[1]} {$m[2]} {$m[3]}:{$m[4]}", 'UTC');
                    // If this date is in the past, assume next year
                    if ($time->isPast()) {
                        $time->addYear();
                    }

                    return $time;
                } catch (\Exception $e) {
                    return null;
                }
            }
        } catch (\Exception $e) {
            \Log::debug('Failed to parse event time', [
                'time_str' => $timeStr,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Clear calendar cache (call when you want fresh data)
     */
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey('forexfactory'));
        Cache::forget($this->cacheKey('finnhub'));
    }

    private function cacheKey(string $provider): string
    {
        return self::CACHE_KEY_PREFIX . $provider;
    }

    private function cacheTtl(): int
    {
        return max(60, (int) config('services.news.cache_ttl', self::DEFAULT_CACHE_TTL));
    }

    /**
     * Get empty calendar structure
     */
    private function getEmptyCalendar(): array
    {
        return [];
    }

    private function fetchForexFactoryEvents(): array
    {
        try {
            $jsonRows = $this->fetchForexFactoryJsonRows();
            if (!empty($jsonRows)) {
                return $this->normalizeForexFactoryJsonRows($jsonRows);
            }

            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 15,
            ])
                ->timeout(15)
                ->get(self::FOREXFACTORY_HTML_URL);

            if (!$response->ok()) {
                return $this->getEmptyCalendar();
            }

            return $this->parseCalendarHtml($response->body());
        } catch (\Exception $e) {
            \Log::warning('ForexFactory calendar fetch failed', [
                'error' => $e->getMessage(),
                'url' => self::FOREXFACTORY_HTML_URL,
            ]);

            return $this->getEmptyCalendar();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchFinnhubRows(): array
    {
        $apiKey = $this->resolveFinnhubApiKey();
        if ($apiKey === '') {
            static $loggedMissingApiKey = false;
            if (!$loggedMissingApiKey) {
                $loggedMissingApiKey = true;
                \Log::warning('Finnhub economic calendar skipped: API key missing.');
            }
            return [];
        }

        $baseUrl = rtrim((string) config('services.finnhub.base_url', 'https://finnhub.io/api/v1'), '/');
        $endpoint = $baseUrl . self::FINNHUB_ECONOMIC_PATH;
        $now = Carbon::now('UTC');

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get($endpoint, [
                    'from' => $now->copy()->subDay()->toDateString(),
                    'to' => $now->copy()->addDays(10)->toDateString(),
                    'token' => $apiKey,
                ]);

            if (!$response->ok()) {
                \Log::warning('Finnhub economic calendar fetch failed', [
                    'status' => $response->status(),
                    'endpoint' => $endpoint,
                ]);

                return [];
            }

            $decoded = $response->json();
            if (is_array($decoded)) {
                if (is_array($decoded['economicCalendar'] ?? null)) {
                    return $decoded['economicCalendar'];
                }

                if (is_array($decoded['data'] ?? null)) {
                    return $decoded['data'];
                }

                return array_is_list($decoded) ? $decoded : [];
            }
        } catch (\Throwable $exception) {
            \Log::warning('Finnhub economic calendar fetch exception', [
                'endpoint' => $endpoint,
                'error' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    private function resolveFinnhubApiKey(): string
    {
        $apiKey = trim((string) config('services.finnhub.api_key', ''));
        if ($apiKey !== '') {
            return $apiKey;
        }

        $envKey = trim((string) env('FINNHUB_API_KEY', ''));
        if ($envKey !== '') {
            return $envKey;
        }

        $processKey = getenv('FINNHUB_API_KEY');
        if (is_string($processKey)) {
            $processKey = trim($processKey);
            if ($processKey !== '') {
                return $processKey;
            }
        }

        return '';
    }

    /**
     * Fetch JSON rows from ForexFactory live feed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchForexFactoryJsonRows(): array
    {
        $rows = [];

        foreach (self::FOREXFACTORY_JSON_URLS as $url) {
            try {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->withHeaders(['User-Agent' => 'EA-Monster-Dashboard/1.0'])
                    ->get($url);

                if ($response->ok() && is_array($response->json())) {
                    $rows = array_merge($rows, $response->json());
                    continue;
                }

                $response = Http::withoutVerifying()
                    ->timeout(20)
                    ->acceptJson()
                    ->withHeaders(['User-Agent' => 'EA-Monster-Dashboard/1.0'])
                    ->get($url);

                if ($response->ok() && is_array($response->json())) {
                    $rows = array_merge($rows, $response->json());
                    continue;
                }

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 20,
                        'header' => "User-Agent: EA-Monster-Dashboard/1.0\r\nAccept: application/json\r\n",
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);

                $raw = @file_get_contents($url, false, $context);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($decoded)) {
                    $rows = array_merge($rows, $decoded);
                }
            } catch (\Throwable $exception) {
                \Log::debug('ForexFactory JSON fetch failed', [
                    'url' => $url,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $rows;
    }

    /**
     * Normalize JSON feed rows into the shape used by the dashboard and EA.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeForexFactoryJsonRows(array $rows): array
    {
        $events = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $country = $this->normalizeCountryCode((string) ($row['country'] ?? ''));
            if ($country !== 'USD') {
                continue;
            }

            $impactLevel = $this->normalizeImpactLevel((string) ($row['impact'] ?? ''));
            if ($impactLevel === null) {
                continue;
            }

            $dateValue = (string) ($row['date'] ?? '');
            if ($dateValue === '') {
                continue;
            }

            try {
                $eventTime = Carbon::parse($dateValue);
            } catch (\Throwable) {
                continue;
            }

            if ($eventTime->isPast()) {
                continue;
            }

            $title = (string) ($row['title'] ?? $row['event'] ?? 'USD Event');
            $events[] = [
                'event_id' => (string) ($row['id'] ?? $row['event_id'] ?? md5($country . '|' . $title . '|' . $eventTime->timestamp)),
                'time' => $eventTime->timestamp,
                'time_formatted' => $eventTime->toDateTimeString(),
                'country' => $country,
                'event' => $title,
                'importance' => $impactLevel,
                'forecast' => (string) ($row['forecast'] ?? $row['Forecast'] ?? ''),
                'previous' => (string) ($row['previous'] ?? $row['Previous'] ?? ''),
            ];
        }

        usort($events, fn (array $a, array $b): int => $a['time'] <=> $b['time']);

        return $events;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFinnhubRows(array $rows): array
    {
        $events = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $country = $this->normalizeCountryCode((string) ($row['country'] ?? $row['countryCode'] ?? ''));
            if ($country !== 'USD') {
                continue;
            }

            $impactLevel = $this->normalizeImpactLevel((string) ($row['impact'] ?? $row['importance'] ?? $row['volatility'] ?? 'MEDIUM'));
            if ($impactLevel === null) {
                continue;
            }

            $eventTime = $this->parseFlexibleEventTime($row);
            if ($eventTime === null || $eventTime->isPast()) {
                continue;
            }

            $title = trim((string) ($row['event'] ?? $row['event_name'] ?? $row['indicator'] ?? $row['title'] ?? 'USD Event'));

            $events[] = [
                'event_id' => (string) ($row['id'] ?? $row['event_id'] ?? md5($country . '|' . $title . '|' . $eventTime->timestamp)),
                'time' => $eventTime->timestamp,
                'time_formatted' => $eventTime->toDateTimeString(),
                'country' => $country,
                'event' => $title,
                'importance' => $impactLevel,
                'actual' => (string) ($row['actual'] ?? ''),
                'forecast' => (string) ($row['forecast'] ?? $row['estimate'] ?? $row['consensus'] ?? ''),
                'previous' => (string) ($row['previous'] ?? $row['prev'] ?? ''),
            ];
        }

        usort($events, fn (array $a, array $b): int => $a['time'] <=> $b['time']);

        return $events;
    }

    private function parseFlexibleEventTime(array $row): ?Carbon
    {
        $timeValue = $row['time'] ?? null;
        if (is_numeric($timeValue)) {
            $time = (int) $timeValue;
            if ($time > 9999999999) {
                $time = (int) floor($time / 1000);
            }

            if ($time > 0) {
                try {
                    return Carbon::createFromTimestampUTC($time);
                } catch (\Throwable) {
                    // Continue to string parsing.
                }
            }
        }

        if (is_string($timeValue) && trim($timeValue) !== '') {
            try {
                return Carbon::parse($timeValue);
            } catch (\Throwable) {
                // Continue to additional field parsing.
            }
        }

        foreach (['date', 'datetime', 'atDate', 'releaseDate', 'time_formatted'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                // Try next field.
            }
        }

        return null;
    }

    private function normalizeCountryCode(string $country): string
    {
        $normalized = strtoupper(trim($country));

        return in_array($normalized, ['USD', 'US', 'USA'], true) ? 'USD' : $normalized;
    }

    /**
     * Determine impact level from ForexFactory HTML snippets.
     * Returns HIGH, MEDIUM, LOW, or null for unsupported rows.
     */
    private function determineImpactLevel(string $impactHtml): ?string
    {
        return $this->normalizeImpactLevel($impactHtml);
    }

    /**
     * Normalize any impact token into HIGH, MEDIUM, or LOW.
     */
    private function normalizeImpactLevel(string $impactToken): ?string
    {
        $normalized = strtolower(trim($impactToken));

        if ($normalized === '') {
            return null;
        }

        if (
            str_contains($normalized, 'high') ||
            str_contains($normalized, 'red') ||
            str_contains($normalized, 'ff-impact--high') ||
            str_contains($normalized, '3')
        ) {
            return 'HIGH';
        }

        if (
            str_contains($normalized, 'medium') ||
            str_contains($normalized, 'orange') ||
            str_contains($normalized, 'ff-impact--medium') ||
            str_contains($normalized, '2')
        ) {
            return 'MEDIUM';
        }

        if (
            str_contains($normalized, 'low') ||
            str_contains($normalized, 'yellow') ||
            str_contains($normalized, 'ff-impact--low') ||
            str_contains($normalized, '1')
        ) {
            return 'LOW';
        }

        return null;
    }
}
