<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use ArtisanPackUI\Analytics\Models\PageView;
use ArtisanPackUI\Analytics\Services\AnalyticsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Realtime dashboard controller.
 *
 * Shadows `ArtisanPackUI\Analytics\Http\Controllers\InertiaDashboardController::realtime`
 * so the Analytics/Realtime page can render a "recent pageviews" list.
 * The vendor's realtime endpoint only returns `active_visitors` and
 * `timestamp` (see `AnalyticsQuery::getRealtime()`), so the recent-hits
 * table was rendering empty. Query `analytics_page_views` directly for
 * the same short window, cap the result set, and expose a JSON endpoint
 * the client polls to keep the list live without hammering the DB.
 */
class RealtimeController extends Controller
{
    private const WINDOW_MINUTES = 5;

    private const RECENT_LIMIT = 20;

    public function __construct(
        private readonly AnalyticsQuery $analyticsQuery,
    ) {}

    public function show(Request $request): Response
    {
        $minutes = $this->resolveMinutes($request);
        $realtime = $this->analyticsQuery->getRealtime($minutes);

        return Inertia::render('Analytics/Realtime', [
            'realtime' => array_merge($realtime, [
                'recent_pageviews' => $this->recentPageViews($minutes),
                'window_minutes' => $minutes,
            ]),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $minutes = $this->resolveMinutes($request);
        $realtime = $this->analyticsQuery->getRealtime($minutes);

        return response()->json([
            'active_visitors' => $realtime['active_visitors'] ?? 0,
            'recent_pageviews' => $this->recentPageViews($minutes),
            'window_minutes' => $minutes,
            'timestamp' => $realtime['timestamp'] ?? now()->toIso8601String(),
        ]);
    }

    /**
     * @return array<int, array{path: string, title: string|null, timestamp: string, visitor_id: string|null}>
     */
    private function recentPageViews(int $minutes): array
    {
        return PageView::query()
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->latest('created_at')
            ->limit(self::RECENT_LIMIT)
            ->get(['path', 'title', 'created_at', 'visitor_id'])
            ->map(fn (PageView $view): array => [
                'path' => (string) $view->path,
                'title' => $view->title,
                'timestamp' => $view->created_at?->toIso8601String() ?? '',
                'visitor_id' => $view->visitor_id,
            ])
            ->all();
    }

    private function resolveMinutes(Request $request): int
    {
        return max(1, min(30, $request->integer('minutes', self::WINDOW_MINUTES)));
    }
}
