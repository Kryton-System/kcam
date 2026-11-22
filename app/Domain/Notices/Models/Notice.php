<?php

declare(strict_types=1);

namespace App\Domain\Notices\Models;

use App\Domain\School\Models\SchoolClass;
use App\Models\User;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\NoticeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'title',
        'title_bn',
        'body',
        'body_bn',
        'audience',
        'target_class_id',
        'channel',
        'sent_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    protected static function newFactory(): NoticeFactory
    {
        return NoticeFactory::new();
    }

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
