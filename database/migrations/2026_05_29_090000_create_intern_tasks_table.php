<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create intern_tasks table
        Schema::dropIfExists('intern_tasks');
        
        Schema::create('intern_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('task');
            $table->enum('status', ['todo', 'progress', 'done'])->default('todo');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2. Restrict permissions for Magang: dashboard_view only
        $magangRole = Role::where('name', 'Magang')->first();
        if ($magangRole) {
            $permissionIds = Permission::whereIn('code', [
                'dashboard_view'
            ])->pluck('id_permission')->toArray();

            $magangRole->permissions()->sync($permissionIds);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_tasks');

        // Restore permissions for Magang: dashboard_view, tiket_manage, inventory_manage
        $magangRole = Role::where('name', 'Magang')->first();
        if ($magangRole) {
            $permissionIds = Permission::whereIn('code', [
                'dashboard_view',
                'tiket_manage',
                'inventory_manage'
            ])->pluck('id_permission')->toArray();

            $magangRole->permissions()->sync($permissionIds);
        }
    }
};
