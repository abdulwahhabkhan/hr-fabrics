<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AuditReportRequest;
use App\Http\Resources\Settings\AuditReportResource;
use App\Models\AuditReport;
use App\Models\Purchase\FabricReceiving;
use App\Services\AuditReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master user tool to run the sales / purchases audit and browse the stored reports.
 */
final class MasterAuditReportController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('masterTools', FabricReceiving::class);

        return Inertia::render('settings/Master/AuditReportIndex', [
            'reports' => AuditReportResource::collection(AuditReport::query()
                ->with('user:id,name')
                ->select(['id', 'start_date', 'end_date', 'user_id', 'source', 'issues_count', 'summary', 'duration_ms', 'created_at'])
                ->latest('id')
                ->paginate(20)),
            'today' => today()->toDateString(),
        ]);
    }

    public function show(AuditReport $auditReport): Response
    {
        Gate::authorize('masterTools', FabricReceiving::class);

        return Inertia::render('settings/Master/AuditReportShow', [
            'report' => $auditReport->load('user:id,name'),
            'modules' => AuditReportService::MODULES,
        ]);
    }

    public function store(AuditReportRequest $request, AuditReportService $service): RedirectResponse
    {
        $report = $service->run($request->startDate(), $request->endDate(), $request->user(), 'web');

        return Redirect::route('settings.master.audit-reports.show', $report)->with(
            $report->hasIssues() ? 'error' : 'success',
            $report->hasIssues() ? "{$report->issues_count} issue(s) found." : 'No issues found.'
        );
    }

    /**
     * Soft delete a stored report; the row stays in the table for auditing.
     */
    public function destroy(AuditReport $auditReport): RedirectResponse
    {
        Gate::authorize('masterTools', FabricReceiving::class);

        $auditReport->delete();

        return Redirect::route('settings.master.audit-reports.index')
            ->with('success', "Audit report #{$auditReport->id} deleted.");
    }
}
