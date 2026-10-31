<?php

namespace TCG\Voyager\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'voyager_activity_logs';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'changed_fields' => 'array',
        'metadata'       => 'array',
        'schema_version' => 'integer',
    ];

    public function getOccurredAtAttribute($value): ?Carbon
    {
        return $value ? Carbon::parse($value, 'UTC') : null;
    }

    public function getRecordedAtAttribute($value): ?Carbon
    {
        return $value ? Carbon::parse($value, 'UTC') : null;
    }

    public function occurredAtInAppTimezone(): ?Carbon
    {
        return $this->occurred_at?->copy()->timezone(config('app.timezone', 'UTC'));
    }

    public function recordedAtInAppTimezone(): ?Carbon
    {
        return $this->recorded_at?->copy()->timezone(config('app.timezone', 'UTC'));
    }
}
