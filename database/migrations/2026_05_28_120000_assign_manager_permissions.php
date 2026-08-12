<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Role;
use App\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Find Manajer Role
        $managerRole = Role::where('name', 'Manajer')->orWhere('id_role', 2)->first();
        if ($managerRole) {
            // Find pelanggan_manage and billing_view permissions
            $permissionIds = Permission::whereIn('code', ['pelanggan_manage', 'billing_view'])
                ->pluck('id_permission')
                ->toArray();
                
            // Attach permissions to Manajer role without duplicates
            $managerRole->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    public function down(): void
    {
        $managerRole = Role::where('name', 'Manajer')->orWhere('id_role', 2)->first();
        if ($managerRole) {
            $permissionIds = Permission::whereIn('code', ['pelanggan_manage', 'billing_view'])
                ->pluck('id_permission')
                ->toArray();
                
            $managerRole->permissions()->detach($permissionIds);
        }
    }
};
