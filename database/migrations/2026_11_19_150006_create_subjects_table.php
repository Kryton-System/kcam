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
        Schema::create('subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('name_bn', 100)->nullable();
            $table->string('code', 20)->nullable();
            $table->unsignedSmallInteger('full_marks')->default(100);
            $table->unsignedSmallInteger('pass_marks')->default(33);
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'class_id', 'name']);
            $table->index(['school_id', 'class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
