<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        // Hanya Yayasan dan Admin Unit yang bisa mengatur jadwal
        if (!($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403, 'Hanya Admin/Yayasan yang dapat mengatur jadwal kerja.');
        }

        // Get all users for the dropdown (only employees)
        $allUsers = User::where('role', 'pegawai')
            ->when($currentUser->isAdminUnit(), function($q) use ($currentUser) {
                return $q->where('unit', $currentUser->unit);
            })
            ->orderBy('name')
            ->get();

        // Determination of target user (default to first employee in list)
        $firstPegawaiId = $allUsers->first()?->id ?? $currentUser->id;
        $targetUserId = $request->get('user_id', $firstPegawaiId);
        
        // Admin Unit hanya bisa mengatur jadwal di unitnya sendiri
        if ($currentUser->isAdminUnit() && User::find($targetUserId)?->unit !== $currentUser->unit) {
             $targetUserId = $firstPegawaiId;
        }

        $targetUser = User::with('schedules')->findOrFail($targetUserId);

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $userSchedules = $targetUser->schedules->keyBy('day_index');

        return view('kepegawaian.jadwal', compact('targetUser', 'allUsers', 'days', 'userSchedules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'schedule' => 'required|array',
            'jam_masuk' => 'required|array',
            'jam_pulang' => 'required|array',
        ]);

        $currentUser = Auth::user();
        if (!($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403);
        }

        foreach ($request->schedule as $dayIndex => $minutes) {
            UserSchedule::updateOrCreate(
                ['user_id' => $request->user_id, 'day_index' => $dayIndex],
                [
                    'minutes' => $minutes,
                    'jam_masuk' => $request->jam_masuk[$dayIndex] ?? null,
                    'jam_pulang' => $request->jam_pulang[$dayIndex] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Jadwal kerja berhasil diperbarui.');
    }
}
