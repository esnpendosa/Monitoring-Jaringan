<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // null = broadcast to all
            $table->string('type');          // tiket_baru, tagihan_lunas, upgrade_paket, system, dll
            $table->string('title');
            $table->text('body');
            $table->string('icon')->default('bx-bell');        // boxicon class
            $table->string('color')->default('primary');       // primary, success, warning, danger, info
            $table->string('action_url')->nullable();          // Link when clicked
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
