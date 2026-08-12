<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Routing\Route;

use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // Self-healing database permission for Inventory
    try {
      if (\Illuminate\Support\Facades\Schema::hasTable('permissions') && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
        $perm = \App\Models\Permission::firstOrCreate(
          ['code' => 'inventory_manage'],
          ['name' => 'Manage Inventory', 'module' => 'Inventory']
        );
        $adminRole = \App\Models\Role::where('name', 'Admin')->first();
        if ($adminRole) {
          $hasPerm = $adminRole->permissions()->where('code', 'inventory_manage')->exists();
          if (!$hasPerm) {
            $adminRole->permissions()->attach($perm->id_permission);
          }
        }
      }
    } catch (\Exception $e) {
      // Safe fallback
    }

    // Self-healing database column for Inventory Harga Beli
    try {
      if (\Illuminate\Support\Facades\Schema::hasTable('inventory_items')) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('inventory_items', 'harga_beli')) {
          \Illuminate\Support\Facades\Schema::table('inventory_items', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->decimal('harga_beli', 15, 2)->nullable()->after('serial_number');
          });
        }
      }
    } catch (\Exception $e) {
      // Safe fallback
    }

    // Self-healing database column for Pelanggan Foto Rumah
    try {
      if (\Illuminate\Support\Facades\Schema::hasTable('pelanggan')) {
        if (!\Illuminate\Support\Facades\Schema::hasColumn('pelanggan', 'foto_rumah')) {
          \Illuminate\Support\Facades\Schema::table('pelanggan', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('foto_rumah')->nullable()->after('alamat');
          });
        }
      }
    } catch (\Exception $e) {
      // Safe fallback
    }

    // Self-healing technician synchronization
    try {
      if (\Illuminate\Support\Facades\Schema::hasTable('roles') && \Illuminate\Support\Facades\Schema::hasTable('users') && \Illuminate\Support\Facades\Schema::hasTable('teknisi')) {
        // Fetch all roles that are NOT Pelanggan (Admin, Manajer, Teknisi, etc.)
        $staffRoles = \App\Models\Role::where('name', '!=', 'Pelanggan')->pluck('id_role');
        if ($staffRoles->isNotEmpty()) {
          $teknisiUserIds = \App\Models\User::whereIn('id_role', $staffRoles)->pluck('id');
          
          // Auto-sync / Create active technician profiles for all staff users
          foreach ($teknisiUserIds as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
              $teknisi = \App\Models\Teknisi::where('id_user', $user->id)->first();
              if (!$teknisi) {
                \App\Models\Teknisi::create([
                  'id_user' => $user->id,
                  'nama_teknisi' => $user->name,
                  'no_hp' => $user->no_wa ?? '',
                  'base_latitude' => 0.0,
                  'base_longitude' => 0.0,
                  'is_active' => true
                ]);
              } else {
                $teknisi->update([
                  'nama_teknisi' => $user->name,
                  'no_hp' => $user->no_wa ?? '',
                  'is_active' => true
                ]);
              }
            }
          }

          // Deactivate technician profiles of users who are now Pelanggan or no longer hold staff roles
          \App\Models\Teknisi::whereNotNull('id_user')
            ->whereNotIn('id_user', $teknisiUserIds)
            ->update(['is_active' => false]);
        }
      }
    } catch (\Exception $e) {
      // Safe fallback
    }

    $verticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
    $verticalMenuData = json_decode($verticalMenuJson);

    try {
      if (\Illuminate\Support\Facades\Schema::hasTable('pelanggan')) {
        $pendingCount = \App\Models\Pelanggan::where('kode_pelanggan', 'like', 'REG%')
          ->where('is_active', 0)
          ->count();
        if ($pendingCount > 0) {
          foreach ($verticalMenuData->menu as $item) {
            if (isset($item->slug) && $item->slug === 'registrasi') {
              $item->badge = ['danger', (string)$pendingCount];
            }
          }
        }
      }
    } catch (\Exception $e) {
      // Safe fallback
    }

    // Share all menuData to all the views
    $this->app->make('view')->share('menuData', [$verticalMenuData]);
  }
}
