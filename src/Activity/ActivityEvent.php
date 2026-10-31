<?php

namespace TCG\Voyager\Activity;

class ActivityEvent
{
    public ?string $eventUuid = null;
    public ?string $source = null;
    public ?string $actorType = null;
    public ?string $actorId = null;
    public ?string $requestId = null;
    public ?string $operationId = null;
    public ?string $ipAddress = null;
    public ?string $routeName = null;

    public function __construct(
        public string $category,
        public string $event,
        public string $result,
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?string $resource = null,
        public array $changedFields = [],
        public array $metadata = []
    ) {
    }
}
