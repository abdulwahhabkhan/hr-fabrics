<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Attendance\AttendanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'worker_name' => (string) $request->input('worker_name', ''),
            'start_date' => (string) $request->input('start_date') ?: today()->toDateString(),
            'end_date' => (string) $request->input('end_date') ?: today()->toDateString(),
        ];

        $rows = AttendanceLog::query()
            ->dailyReport($filters)
            ->paginate(100)
            ->appends($request->all())
            ->through(fn (AttendanceLog $row): array => [
                'worker_name' => $row->worker_name,
                'punch_date' => $row->punch_date->toDateString(),
                'first_punch' => $row->first_punch,
                'last_punch' => $row->last_punch,
                'punch_count' => $row->punch_count,
            ]);

        return Inertia::render('Reports/Attendance/AttendanceReport', [
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    public function detail(Request $request): Response
    {
        $filters = [
            'worker_name' => (string) $request->input('worker_name', ''),
            'punch_date' => (string) $request->input('punch_date', today()->toDateString()),
        ];

        $punches = AttendanceLog::query()
            ->where('worker_name', $filters['worker_name'])
            ->where('punch_date', $filters['punch_date'])
            ->orderBy('punch_time')
            ->get(['id', 'external_id', 'worker_name', 'punch_date', 'punch_time', 'method'])
            ->map(fn (AttendanceLog $log): array => [
                'id' => $log->id,
                'external_id' => $log->external_id,
                'worker_name' => $log->worker_name,
                'punch_date' => $log->punch_date->toDateString(),
                'punch_time' => $log->punch_time,
                'method' => $log->method,
            ]);

        return Inertia::render('Reports/Attendance/AttendanceReportDetail', [
            'filters' => $filters,
            'punches' => $punches,
        ]);
    }
}
