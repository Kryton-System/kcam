<?php

declare(strict_types=1);

namespace App\Domain\Transport\Models;

use App\Domain\Student\Models\Enrollment;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\TransportAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportAssignment extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = ['school_id', 'route_id', 'enrollment_id', 'stop', 'monthly_fee'];

    protected function casts(): array
    {
        return ['monthly_fee' => 'integer'];
    }

    protected static function newFactory(): TransportAssignmentFactory
    {
        return TransportAssignmentFactory::new();
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
