<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Unit;
use Illuminate\Support\Facades\Hash;

class AdminUnitController extends Controller
{
    public function index()
    {
        $admins = User::where('role', User::ROLE_ADMIN_UNIT)->orderBy('name', 'asc')->get();
        $units = Unit::orderBy('nama', 'asc')->get();
        return view('kepegawaian.admin_unit', compact('admins', 'units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'unit' => 'required|string',
        ]);

        $username = explode('@', $request->email)[0];
        $original = $username;
        $count = 1;
        while (User::where('username', $username)->exists()) {
            $username = $original . $count;
            $count++;
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_ADMIN_UNIT,
            'unit' => $request->unit,
        ]);

        return redirect()->back()->with('success', 'Admin Unit baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        if ($user->role !== User::ROLE_ADMIN_UNIT) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'unit' => 'required|string',
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'unit' => $request->unit,
        ];

        // Generate username if empty or if email has changed
        if (!$user->username || $user->email !== $request->email) {
            $username = explode('@', $request->email)[0];
            $original = $username;
            $count = 1;
            while (User::where('username', $username)->where('id', '!=', $id)->exists()) {
                $username = $original . $count;
                $count++;
            }
            $data['username'] = $username;
        }

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('success', 'Data Admin Unit berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->role !== User::ROLE_ADMIN_UNIT) {
            abort(403);
        }
        $user->delete();
        return redirect()->back()->with('success', 'Akun Admin Unit berhasil dihapus.');
    }
}
