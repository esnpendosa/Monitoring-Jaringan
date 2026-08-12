<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Role;
use App\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Magang Role
        $magangRole = Role::updateOrCreate(
            ['name' => 'Magang'],
            ['description' => 'Intern with support and warehouse inventory access']
        );

        // 2. Sync permissions for Magang: dashboard, support tickets, and inventory management
        $permissionIds = Permission::whereIn('code', [
            'dashboard_view',
            'tiket_manage',
            'inventory_manage'
        ])->pluck('id_permission')->toArray();

        $magangRole->permissions()->sync($permissionIds);
    }

    public function down(): void
    {
        $magangRole = Role::where('name', 'Magang')->first();
        if ($magangRole) {
            $magangRole->permissions()->detach();
            $magangRole->delete();
        }
    }
};
