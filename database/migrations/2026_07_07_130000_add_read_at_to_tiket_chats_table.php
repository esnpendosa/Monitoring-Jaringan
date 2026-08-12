<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiket_chats', function (Blueprint $table) {
            // Untuk tracking pesan yang belum dibaca per user
            $table->timestamp('read_at')->nullable()->after('image_path');
            $table->index(['id_tiket', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tiket_chats', function (Blueprint $table) {
            $table->dropIndex(['id_tiket', 'read_at']);
            $table->dropColumn('read_at');
        });
    }
};
