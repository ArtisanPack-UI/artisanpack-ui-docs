<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Packages\Changelog;
use Modules\Packages\Documentation;
use Modules\Packages\Package;

/**
 * `/dashboard/audit-log` viewer (V2_REFACTOR_PLAN.md §4.3, §9.6 item #46).
 *
 * Admin-only. Renders a paginated feed of every write recorded by
 * {@see AuditLogger}, with filters for resource type, actor, source,
 * and date range.
 */
class AuditLogController extends Controller
{
    /**
     * Resource types eligible for the resource filter — the short label
     * shown in the UI paired with the FQCN stored in `resource_type`.
     *
     * @var array<string, class-string>
     */
    protected const RESOURCE_MAP = [
        'package' => Package::class,
        'documentation' => Documentation::class,
        'changelog' => Changelog::class,
    ];

    public function index(Request $request): Response
    {
        $filters = $this->extractFilters($request);

        $query = AuditLog::query()->with('user:id,name,email');

        if ($filters['resource'] !== null && isset(self::RESOURCE_MAP[$filters['resource']])) {
            $query->where('resource_type', self::RESOURCE_MAP[$filters['resource']]);
        }

        if ($filters['actor'] !== null && $filters['actor'] !== '') {
            $query->where('actor_name', 'like', '%'.$filters['actor'].'%');
        }

        if ($filters['source'] !== null) {
            $query->where('source', $filters['source']);
        }

        if ($filters['from'] !== null) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if ($filters['to'] !== null) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $entries = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $labelFor = array_flip(self::RESOURCE_MAP);

        return Inertia::render('Dashboard/AuditLog', [
            'entries' => [
                'data' => $entries->getCollection()->map(fn (AuditLog $entry): array => [
                    'id' => $entry->id,
                    'actor_name' => $entry->actor_name,
                    'user' => $entry->user ? [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                        'email' => $entry->user->email,
                    ] : null,
                    'source' => $entry->source,
                    'resource_type' => $entry->resource_type,
                    'resource_label' => $labelFor[$entry->resource_type] ?? $entry->resource_type,
                    'resource_id' => $entry->resource_id,
                    'action' => $entry->action,
                    'changes' => $entry->changes,
                    'ip_address' => $entry->ip_address,
                    'created_at' => optional($entry->created_at)->toIso8601String(),
                ])->all(),
                'meta' => [
                    'current_page' => $entries->currentPage(),
                    'last_page' => $entries->lastPage(),
                    'per_page' => $entries->perPage(),
                    'total' => $entries->total(),
                ],
                'links' => [
                    'prev' => $entries->previousPageUrl(),
                    'next' => $entries->nextPageUrl(),
                ],
            ],
            'filters' => [
                'resource' => $filters['resource'],
                'actor' => $filters['actor'],
                'source' => $filters['source'],
                'from' => $filters['from']?->toDateString(),
                'to' => $filters['to']?->toDateString(),
            ],
            'resourceOptions' => array_map(
                fn (string $key): array => ['value' => $key, 'label' => ucfirst($key)],
                array_keys(self::RESOURCE_MAP),
            ),
            'sourceOptions' => [
                ['value' => AuditLogger::SOURCE_WEB, 'label' => 'Web'],
                ['value' => AuditLogger::SOURCE_API, 'label' => 'API'],
            ],
        ]);
    }

    /**
     * @return array{
     *     resource: string|null,
     *     actor: string|null,
     *     source: string|null,
     *     from: Carbon|null,
     *     to: Carbon|null
     * }
     */
    protected function extractFilters(Request $request): array
    {
        $validated = $request->validate([
            'resource' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::RESOURCE_MAP))],
            'actor' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'in:'.AuditLogger::SOURCE_WEB.','.AuditLogger::SOURCE_API],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return [
            'resource' => $validated['resource'] ?? null,
            'actor' => $validated['actor'] ?? null,
            'source' => $validated['source'] ?? null,
            'from' => isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : null,
            'to' => isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : null,
        ];
    }
}
