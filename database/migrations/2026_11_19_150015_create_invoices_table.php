<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('month', 20)->nullable(); // e.g. "2026-01" or "January"
            $table->decimal('total', 12, 2)->default(0.00);
            $table->decimal('paid', 12, 2)->default(0.00);
            $table->decimal('due', 12, 2)->default(0.00);
            $table->string('status', 20)->default('unpaid'); // unpaid, partial, paid, cancelled
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'student_id', 'status']);
            $table->index(['school_id', 'academic_year_id', 'month']);
            $table->index(['school_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
