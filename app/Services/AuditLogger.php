<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Writes rows to the `audit_log` table (V2_REFACTOR_PLAN.md §4.3,
 * §9.6 item #46).
 *
 * Invoke from web and API controllers after every successful write to
 * Packages / Documentation / Changelogs. The service resolves actor,
 * source, and IP from the current request so callers only supply what
 * they're changing.
 */
class AuditLogger
{
    public const SOURCE_WEB = 'web';

    public const SOURCE_API = 'api';

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_REORDERED = 'reordered';

    /**
     * Record a write against a single model.
     *
     * `$original` is the model's `getOriginal()` snapshot from BEFORE
     * the change; `$changed` is the model's current attributes. The
     * service computes the diff and only stores keys that actually
     * moved so the JSON payload stays small.
     *
     * @param  array<string, mixed>|null  $original
     * @param  array<string, mixed>|null  $changed
     */
    public function record(
        string $action,
        Model $resource,
        ?array $original = null,
        ?array $changed = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        $diff = $this->diff($original, $changed);

        return AuditLog::create([
            'user_id' => $this->resolveUserId($request),
            'actor_name' => $this->resolveActorName($request),
            'source' => $this->resolveSource($request),
            'resource_type' => $resource::class,
            'resource_id' => $resource->getKey(),
            'action' => $action,
            'changes' => $diff === [] ? null : $diff,
            'ip_address' => $request?->ip(),
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Record a reorder — where the changed payload is a positional map
     * rather than an attribute diff, so we store it verbatim under a
     * single `order` key.
     *
     * @param  array<int, array{id: int, menu_order: int}>  $items
     */
    public function recordReorder(
        Model $resource,
        array $items,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'user_id' => $this->resolveUserId($request),
            'actor_name' => $this->resolveActorName($request),
            'source' => $this->resolveSource($request),
            'resource_type' => $resource::class,
            'resource_id' => $resource->getKey(),
            'action' => self::ACTION_REORDERED,
            'changes' => ['order' => $items],
            'ip_address' => $request?->ip(),
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $original
     * @param  array<string, mixed>|null  $changed
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    protected function diff(?array $original, ?array $changed): array
    {
        if ($original === null && $changed === null) {
            return [];
        }

        if ($original === null) {
            // Creation: every non-null attribute is "new".
            return collect($changed ?? [])
                ->filter(fn ($value): bool => $value !== null)
                ->mapWithKeys(fn ($value, string $key): array => [$key => [null, $value]])
                ->all();
        }

        if ($changed === null) {
            // Deletion: capture the pre-delete snapshot for forensics.
            return collect($original)
                ->mapWithKeys(fn ($value, string $key): array => [$key => [$value, null]])
                ->all();
        }

        $keys = array_unique(array_merge(array_keys($original), array_keys($changed)));
        $diff = [];

        foreach ($keys as $key) {
            $before = $original[$key] ?? null;
            $after = $changed[$key] ?? null;

            if ($before === $after) {
                continue;
            }

            $diff[$key] = [$before, $after];
        }

        return $diff;
    }

    protected function resolveUserId(?Request $request): ?int
    {
        $user = $request?->user();

        return $user instanceof User ? $user->getKey() : null;
    }

    protected function resolveActorName(?Request $request): string
    {
        $user = $request?->user();

        if ($user === null) {
            return 'system';
        }

        // On API requests the acting Sanctum token exposes its name
        // (e.g. "artisanpackui.dev production"); prefer that over the
        // owning user because rotation matters at the token level.
        $token = method_exists($user, 'currentAccessToken')
            ? $user->currentAccessToken()
            : null;

        if ($token instanceof PersonalAccessToken && $token->name !== '') {
            return $user->name.' ('.$token->name.')';
        }

        return $user->name;
    }

    protected function resolveSource(?Request $request): string
    {
        if ($request === null) {
            return self::SOURCE_WEB;
        }

        if ($request->is('api/*') || $request->expectsJson()) {
            return self::SOURCE_API;
        }

        return self::SOURCE_WEB;
    }
}
