<?php

declare(strict_types=1);

namespace App\Domain\Fees\Models;

use App\Domain\Payments\Models\Payment;
use App\Domain\School\Models\AcademicYear;
use App\Domain\Student\Models\Student;
use App\Support\Traits\BelongsToSchool;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToSchool;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'month',
        'total',
        'paid',
        'due',
        'status',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'paid' => 'integer',
            'due' => 'integer',
            'due_date' => 'date',
        ];
    }

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Recalculate totals and due.
     */
    public function recalculate(): void
    {
        $this->total = (int) $this->items()->sum('amount');
        $this->paid = (int) $this->payments()->where('status', 'completed')->sum('amount');
        $this->due = max(0, $this->total - $this->paid);

        if ($this->due <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }
}
