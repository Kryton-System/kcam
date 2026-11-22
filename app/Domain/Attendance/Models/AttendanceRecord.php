<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Student\Models\Enrollment;
use App\Models\User;
use App\Support\Enums\AttendanceStatus;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'date',
        'status',
        'marked_by',
        'client_uuid',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'status' => AttendanceStatus::class,
            'synced_at' => 'datetime',
        ];
    }

    protected static function newFactory(): AttendanceRecordFactory
    {
        return AttendanceRecordFactory::new();
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
