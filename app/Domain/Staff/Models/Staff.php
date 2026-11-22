<?php

declare(strict_types=1);

namespace App\Domain\Staff\Models;

use App\Models\User;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use BelongsToSchool;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'school_id',
        'user_id',
        'name',
        'name_bn',
        'designation',
        'phone',
        'joined_on',
        'salary_basic',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
            'salary_basic' => 'integer',
        ];
    }

    protected static function newFactory(): StaffFactory
    {
        return StaffFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
