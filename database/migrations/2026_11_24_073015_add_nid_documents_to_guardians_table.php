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
        Schema::table('guardians', function (Blueprint $table): void {
            $table->string('nid_front_path')->nullable()->after('nid');
            $table->string('nid_back_path')->nullable()->after('nid_front_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table): void {
            $table->dropColumn(['nid_front_path', 'nid_back_path']);
        });
    }
};
