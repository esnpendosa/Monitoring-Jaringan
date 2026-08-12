<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = User::with('role');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username_email', 'like', "%{$search}%");
            });
        }

        $users = $query->get();
        return view('content.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $pelangganList = \App\Models\Pelanggan::whereNull('id_user')->get();
        return view('content.users.create', compact('roles', 'pelangganList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'id_role' => 'required|exists:roles,id_role',
            'id_pelanggan' => 'nullable|exists:pelanggan,id_pelanggan',
            'pin_fingerspot' => 'nullable|string|max:50|unique:users',
            'no_hp' => 'nullable|string|max:20'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username_email' => $request->email,
            'password' => Hash::make($request->password),
            'id_role' => $request->id_role,
            'pin_fingerspot' => $request->pin_fingerspot,
            'no_hp' => $request->no_hp,
            'is_active' => true
        ]);

        if ($request->id_pelanggan) {
            \App\Models\Pelanggan::where('id_pelanggan', $request->id_pelanggan)->update(['id_user' => $user->id]);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        // Get unlinked customers OR the one already linked to this user
        $pelangganList = \App\Models\Pelanggan::whereNull('id_user')
            ->orWhere('id_user', $user->id)
            ->get();
        return view('content.users.edit', compact('user', 'roles', 'pelangganList'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'id_role' => 'required|exists:roles,id_role',
            'id_pelanggan' => 'nullable|exists:pelanggan,id_pelanggan',
            'pin_fingerspot' => 'nullable|string|max:50|unique:users,pin_fingerspot,' . $user->id,
            'no_hp' => 'nullable|string|max:20'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'id_role' => $request->id_role,
            'pin_fingerspot' => $request->pin_fingerspot,
            'no_hp' => $request->no_hp
        ]);

        if ($request->password) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        // Reset old link and set new link
        \App\Models\Pelanggan::where('id_user', $user->id)->update(['id_user' => null]);
        if ($request->id_pelanggan) {
            \App\Models\Pelanggan::where('id_pelanggan', $request->id_pelanggan)->update(['id_user' => $user->id]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    public function resetCustomerPasswords()
    {
        $pelangganRole = Role::where('name', 'like', '%Pelanggan%')->first();
        
        if (!$pelangganRole) {
            return back()->with('error', 'Role Pelanggan tidak ditemukan.');
        }

        $count = User::where('id_role', $pelangganRole->id_role)->update([
            'password' => Hash::make('12345678')
        ]);

        return redirect()->route('users.index')->with('success', "Berhasil mereset password {$count} pengguna pelanggan menjadi '12345678'.");
    }
}
