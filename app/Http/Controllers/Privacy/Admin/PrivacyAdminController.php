<?php

declare(strict_types=1);

namespace App\Http\Controllers\Privacy\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyAdminController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Privacy/Admin/Dashboard', [
            'links' => [
                ['label' => 'Consents', 'href' => route('dashboard.privacy.consents')],
                ['label' => 'Data requests', 'href' => route('dashboard.privacy.data-requests')],
                ['label' => 'Compliance report', 'href' => route('dashboard.privacy.compliance-report')],
                ['label' => 'Breaches', 'href' => route('dashboard.privacy.breaches')],
            ],
        ]);
    }

    public function consents(): Response
    {
        return Inertia::render('Privacy/Admin/Consents', [
            'endpoint' => route('privacy.api.admin.consents.index'),
        ]);
    }

    public function dataRequests(): Response
    {
        return Inertia::render('Privacy/Admin/DataRequests', [
            'endpoint' => route('privacy.api.admin.data-requests.index'),
        ]);
    }

    public function complianceReport(): Response
    {
        return Inertia::render('Privacy/Admin/ComplianceReport', [
            'endpoint' => route('privacy.api.admin.compliance-report'),
        ]);
    }

    public function breaches(): Response
    {
        return Inertia::render('Privacy/Admin/Breaches', [
            'endpoint' => route('privacy.api.admin.breaches.index'),
            'report_url' => route('dashboard.privacy.breaches.report'),
            'detail_url_template' => route('dashboard.privacy.breaches.show', ['id' => '__ID__']),
        ]);
    }

    public function breachReport(): Response
    {
        return Inertia::render('Privacy/Admin/BreachReport', [
            'endpoint' => route('privacy.api.admin.breaches.store'),
            'index_url' => route('dashboard.privacy.breaches'),
        ]);
    }

    public function breachDetail(int $id): Response
    {
        return Inertia::render('Privacy/Admin/BreachDetail', [
            'breach_id' => $id,
            'endpoint' => route('privacy.api.admin.breaches.index'),
            'index_url' => route('dashboard.privacy.breaches'),
        ]);
    }
}
