<?php

declare(strict_types=1);

namespace App\Domain\Notices\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\SmsLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'to',
        'body',
        'provider_ref',
        'status',
        'cost',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SmsLogFactory
    {
        return SmsLogFactory::new();
    }
}
