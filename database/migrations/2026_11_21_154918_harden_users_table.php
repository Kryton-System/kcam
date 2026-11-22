<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mobile is a login identifier so it must be unique; a user with no explicit role is least privileged.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('mobile');
            $table->string('role', 30)->default('parent')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['mobile']);
            $table->string('role', 30)->default('owner')->change();
        });
    }
};
