<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modems', function (Blueprint $table) {
            // Index untuk filter is_active (query utama selalu pakai WHERE is_active = 1)
            $table->index('is_active', 'modems_is_active_idx');
            // Index komposit untuk ORDER BY merek, model
            $table->index(['is_active', 'merek', 'model'], 'modems_active_merek_model_idx');
        });
    }

    public function down(): void
    {
        Schema::table('modems', function (Blueprint $table) {
            $table->dropIndex('modems_is_active_idx');
            $table->dropIndex('modems_active_merek_model_idx');
        });
    }
};
