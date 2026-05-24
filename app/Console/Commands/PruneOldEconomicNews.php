<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\EconomicNews;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneOldEconomicNews extends Command
{
    protected $signature = 'news:prune-old {--days=14 : Keep only records newer than this many days}';

    protected $description = 'Delete old economic news rows beyond retention period';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $deleted = EconomicNews::query()
            ->where('event_at', '<', $cutoff)
            ->delete();

        $this->info('Economic news cleanup done. Deleted ' . $deleted . ' rows older than ' . $days . ' days.');

        return self::SUCCESS;
    }
}
