<?php

namespace App\Models\Attendance;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => AttendanceStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'worker_id');
    }
}
