<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceLog;
use App\Models\Attendance\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceRegisterController extends Controller
{
    /**
     * Roll call for every employee on a given day. Employees with no
     * attendances row for the date are reported absent.
     */
    public function index(Request $request): Response
    {
        $date = (string) $request->input('date') ?: today()->toDateString();

        $attendances = Attendance::query()
            ->where('date', $date)
            ->get()
            ->keyBy('worker_id');

        $employees = Employee::query()
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($attendances): array {
                $attendance = $attendances->get($employee->id);
                $status = $attendance?->status ?? AttendanceStatus::Absent;

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'in' => $attendance?->in,
                    'out' => $attendance?->out,
                    'status' => $status->value,
                    'status_label' => $status->label(),
                ];
            })
            ->sortBy('in')
            ->values();

        return Inertia::render('Reports/Attendance/AttendanceRegister', [
            'date' => $date,
            'employees' => $employees,
        ]);
    }

    /**
     * A single employee's attendance for one day, plus every raw punch
     * (attendance_logs row) recorded for them that day.
     */
    public function detail(Request $request): Response
    {
        $workerId = (string) $request->input('worker_id');
        $date = (string) $request->input('date') ?: today()->toDateString();

        $employee = Employee::withTrashed()->findOrFail($workerId);

        $attendance = Attendance::query()
            ->where('worker_id', $workerId)
            ->where('date', $date)
            ->first();

        $status = $attendance?->status ?? AttendanceStatus::Absent;

        $logs = AttendanceLog::query()
            ->where('worker_id', $workerId)
            ->where('punch_date', $date)
            ->orderBy('punch_time')
            ->get(['id', 'external_id', 'punch_time', 'method']);

        return Inertia::render('Reports/Attendance/AttendanceRegisterDetail', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
            ],
            'date' => $date,
            'attendance' => [
                'in' => $attendance?->in,
                'out' => $attendance?->out,
                'status' => $status->value,
                'status_label' => $status->label(),
            ],
            'logs' => $logs,
        ]);
    }
}
