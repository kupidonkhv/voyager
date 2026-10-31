<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

class ActivityPruneCommand extends Command
{
    protected $name = 'voyager:activity:prune';

    protected $description = 'Delete Voyager activity log entries older than the retention period';

    public function handle(): int
    {
        $days = $this->option('days');
        $days = $days === null ? (int) config('voyager-activity.retention_days', 180) : (int) $days;

        if ($days < 1) {
            $this->error('Retention days must be at least 1.');

            return self::FAILURE;
        }

        $lockKey = 'voyager-activity-prune-lock';
        if (!Cache::add($lockKey, 1, 3600)) {
            $this->error('Another activity log cleanup is already running.');

            return self::FAILURE;
        }

        try {
            $cutoff = now('UTC')->subDays($days);
            $query = DB::table('voyager_activity_logs')->where('occurred_at', '<', $cutoff);
            $total = (clone $query)->count();

            if ($this->option('dry-run')) {
                $this->info("Dry run: {$total} activity log entries would be deleted.");

                return self::SUCCESS;
            }

            $chunk = max(1, (int) config('voyager-activity.cleanup_chunk_size', 1000));
            $deleted = 0;

            do {
                $ids = (clone $query)->orderBy('id')->limit($chunk)->pluck('id');

                if ($ids->isEmpty()) {
                    break;
                }

                $deleted += DB::table('voyager_activity_logs')->whereIn('id', $ids)->delete();
            } while ($ids->count() === $chunk);

            $this->info("Deleted {$deleted} of {$total} activity log entries older than {$days} days.");

            return self::SUCCESS;
        } finally {
            Cache::forget($lockKey);
        }
    }

    protected function getOptions()
    {
        return [
            ['dry-run', null, InputOption::VALUE_NONE, 'Count entries without deleting them'],
            ['days', null, InputOption::VALUE_REQUIRED, 'Override the configured retention period'],
        ];
    }
}
