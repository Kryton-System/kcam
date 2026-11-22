<?php

declare(strict_types=1);

namespace App\Domain\Payments\Models;

use App\Support\Traits\BelongsToSchool;
use Database\Factories\ReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use BelongsToSchool;
    use HasFactory;

    protected $fillable = [
        'school_id',
        'payment_id',
        'serial_no',
        'pdf_path',
    ];

    protected static function newFactory(): ReceiptFactory
    {
        return ReceiptFactory::new();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
