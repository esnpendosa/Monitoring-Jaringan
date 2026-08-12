<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modems', function (Blueprint $table) {
            $table->renameColumn('image_path', 'image_path_front');
        });
        
        Schema::table('modems', function (Blueprint $table) {
            $table->string('image_path_back')->nullable()->after('image_path_front');
        });
    }

    public function down(): void
    {
        Schema::table('modems', function (Blueprint $table) {
            $table->dropColumn('image_path_back');
        });
        
        Schema::table('modems', function (Blueprint $table) {
            $table->renameColumn('image_path_front', 'image_path');
        });
    }
};
