<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
        });

        // Set default username for existing users based on email prefix
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            $username = explode('@', $user->email)[0];
            // Handle duplicate usernames
            $original = $username;
            $count = 1;
            while (\App\Models\User::where('username', $username)->exists()) {
                $username = $original . $count;
                $count++;
            }
            $user->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
