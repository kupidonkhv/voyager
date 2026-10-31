<?php

namespace TCG\Voyager\Activity;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ActivityWriter
{
    public function write(array $row, string $eventUuid): void
    {
        $table = DB::table('voyager_activity_logs');

        if ((clone $table)->where('event_uuid', $eventUuid)->exists()) {
            return;
        }

        try {
            $table->insert($row);
        } catch (QueryException $e) {
            if ($this->isDuplicate($e) || DB::table('voyager_activity_logs')->where('event_uuid', $eventUuid)->exists()) {
                return;
            }

            throw $e;
        }
    }

    protected function isDuplicate(QueryException $e): bool
    {
        return in_array((string) $e->getCode(), ['23000', '23505'], true)
            || stripos($e->getMessage(), 'unique') !== false
            || stripos($e->getMessage(), 'duplicate') !== false;
    }
}
