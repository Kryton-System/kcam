<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. All money columns are whole poisha.
     */
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
            $table->string('month', 7);
            $table->unsignedBigInteger('basic')->default(0);
            $table->unsignedBigInteger('deductions')->default(0);
            $table->unsignedBigInteger('advances')->default(0);
            $table->unsignedBigInteger('net')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['school_id', 'staff_id', 'month']);
            $table->index(['school_id', 'month', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
