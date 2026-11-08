<?php

use App\Models\AuditReport;
use App\Models\Role;
use App\Models\Sales\Order;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertSoftDeleted;

function auditMaster(): User
{
    return User::factory()->master()->create(['role_id' => Role::factory()->create()->id]);
}

test('regular users cannot use the audit reports even with every permission', function () {
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, [
        'settings.master.audit-reports.index',
        'settings.master.audit-reports.store',
        'settings.master.audit-reports.show',
        'settings.master.audit-reports.destroy',
    ]);
    $report = AuditReport::factory()->create();

    actingAs($user)->get(route('settings.master.audit-reports.index'))->assertForbidden();
    actingAs($user)->get(route('settings.master.audit-reports.show', $report))->assertForbidden();
    actingAs($user)->post(route('settings.master.audit-reports.store'), [
        'start_date' => today()->toDateString(), 'end_date' => today()->toDateString(),
    ])->assertForbidden();
    actingAs($user)->delete(route('settings.master.audit-reports.destroy', $report))->assertForbidden();

    assertDatabaseCount(AuditReport::class, 1);
    expect($report->refresh()->trashed())->toBeFalse();
});

test('master users see the stored reports, newest first, without their issues', function () {
    $old = AuditReport::factory()->create();
    $new = AuditReport::factory()->withIssue()->create();

    $response = actingAs(auditMaster())->get(route('settings.master.audit-reports.index'));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/Master/AuditReportIndex')
        ->where('today', today()->toDateString())
        ->where('reports.data.0.id', $new->id)
        ->where('reports.data.0.issues_count', 1)
        ->where('reports.data.0.checked_count', 1)
        ->where('reports.data.1.id', $old->id)
        ->missing('reports.data.0.issues')
        ->where('reports.meta.total', 2)
    );
});

test('master users run an audit that is stored and opened', function () {
    $master = auditMaster();
    $order = Order::factory()->closed()->create(['allocation' => 2, 'created_at' => now()]);

    $response = actingAs($master)->post(route('settings.master.audit-reports.store'), [
        'start_date' => today()->toDateString(),
        'end_date' => today()->toDateString(),
    ]);

    $report = AuditReport::query()->sole();
    $response->assertRedirect(route('settings.master.audit-reports.show', $report));
    expect($report->user_id)->toBe($master->id)
        ->and($report->source)->toBe('web')
        ->and($report->summary['sale']['checked'])->toBe(1)
        ->and(collect($report->issues)->pluck('id')->unique()->all())->toBe([$order->id]);
});

test('the audit needs a valid date range of at most a year', function (array $dates, string $field) {
    $response = actingAs(auditMaster())->post(route('settings.master.audit-reports.store'), $dates);

    $response->assertSessionHasErrors($field);
    assertDatabaseCount(AuditReport::class, 0);
})->with([
    'missing dates' => [[], 'start_date'],
    'wrong format' => [['start_date' => '08/11/2026', 'end_date' => '2026-11-08'], 'start_date'],
    'end before start' => [['start_date' => '2026-11-08', 'end_date' => '2026-11-01'], 'end_date'],
    'longer than a year' => [['start_date' => '2025-01-01', 'end_date' => '2026-11-08'], 'end_date'],
]);

test('master users open a report with its issues and module labels', function () {
    $report = AuditReport::factory()->withIssue()->create();

    $response = actingAs(auditMaster())->get(route('settings.master.audit-reports.show', $report));

    $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('settings/Master/AuditReportShow')
        ->where('report.id', $report->id)
        ->where('report.issues.0.check', 'Customer debit')
        ->where('modules.sale', 'Sale')
        ->has('modules', 4)
    );
});

test('master users soft delete a report', function () {
    $report = AuditReport::factory()->create();

    actingAs(auditMaster())->delete(route('settings.master.audit-reports.destroy', $report))
        ->assertRedirect(route('settings.master.audit-reports.index'));

    assertSoftDeleted($report);
});
