<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modems', function (Blueprint $table) {
            $table->renameColumn('tipe', 'ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('modems', function (Blueprint $table) {
            $table->renameColumn('ip_address', 'tipe');
        });
    }
};
