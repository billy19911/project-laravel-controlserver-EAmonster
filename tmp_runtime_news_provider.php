<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = app(App\Services\EconomicCalendarService::class);

$result = [
    'config_services_news_provider' => config('services.news.provider'),
    'env_news_calendar_provider' => env('NEWS_CALENDAR_PROVIDER'),
    'service_provider_key' => $svc->providerKey(),
    'service_provider_label' => $svc->providerLabel(),
    'service_total_events' => count($svc->getHighImpactEvents()),
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
