<?php

namespace TCG\Voyager\Activity;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityContext
{
    protected ?string $actorType = null;
    protected ?string $actorId = null;
    protected ?string $source = null;
    protected ?string $requestId = null;
    protected ?string $operationId = null;
    protected ?string $ipAddress = null;
    protected ?string $routeName = null;

    public function reset(): void
    {
        $this->actorType = null;
        $this->actorId = null;
        $this->source = null;
        $this->requestId = null;
        $this->operationId = null;
        $this->ipAddress = null;
        $this->routeName = null;
    }

    public function capture(Request $request, ?string $source = null): void
    {
        $this->source = $source ?: ($request->is('api/*') ? 'api' : 'admin');
        $this->requestId = (string) Str::uuid();
        $this->routeName = $request->route()?->getName();
        $this->ipAddress = $this->resolveIp($request);
        $this->operationId = (string) Str::uuid();

        if ($user = $this->resolveUser()) {
            $this->setActorFromUser($user);
        }
    }

    public function setActorFromUser($user, string $type = 'user'): void
    {
        $this->actorType = $type;
        $this->actorId = $user ? (string) $user->getAuthIdentifier() : null;
    }

    public function setActor(?string $type, $id): void
    {
        $this->actorType = $type;
        $this->actorId = $id === null ? null : (string) $id;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function operationId(): string
    {
        return $this->operationId ??= (string) Str::uuid();
    }

    public function beginOperation(): string
    {
        return $this->operationId = (string) Str::uuid();
    }

    public function actorType(): ?string
    {
        return $this->actorType;
    }

    public function actorId(): ?string
    {
        return $this->actorId;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function routeName(): ?string
    {
        return $this->routeName;
    }

    protected function resolveUser()
    {
        try {
            return Auth::guard(app('VoyagerGuard'))->user();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveIp(Request $request): ?string
    {
        if (!config('voyager-activity.store_ip', true)) {
            return null;
        }

        $ip = $request->ip();

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
}
