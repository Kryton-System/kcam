<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('van_number', 50);
            $table->string('driver_name', 150);
            $table->string('driver_mobile', 20);
            $table->unsignedSmallInteger('capacity');
            $table->timestamps();

            $table->unique(['school_id', 'van_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_vehicles');
    }
};
