<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
            $table->string('name', 150);
            $table->string('name_bn', 150)->nullable();
            $table->text('stops')->nullable();
            $table->timestamps();

            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_routes');
    }
};
