<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('transport_routes')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->string('stop', 150)->nullable();
            // Integer poisha, never a float.
            $table->unsignedInteger('monthly_fee');
            $table->timestamps();

            $table->unique('enrollment_id');
            $table->index(['school_id', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_assignments');
    }
};
