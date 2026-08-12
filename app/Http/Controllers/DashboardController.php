<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\TiketGangguan;
use App\Models\Teknisi;
use App\Models\Router;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 1. Dashboard Teknisi
        $roleName = $user->role ? $user->role->name : '';
        $isTeknisi = ($roleName === 'Teknisi' || $user->id_role == 3);

        if ($isTeknisi) {
            $teknisi = $user->teknisi;
            if (!$teknisi) {
                // Self-healing technician creation
                $teknisi = Teknisi::create([
                    'id_user' => $user->id,
                    'nama_teknisi' => $user->name,
                    'no_hp' => $user->no_wa ?? $user->no_hp ?? '',
                    'base_latitude' => 0.0,
                    'base_longitude' => 0.0,
                    'is_active' => true
                ]);
            }

            // Stats for technician
            $stats = [
                'my_tickets_count' => TiketGangguan::where('id_teknisi', $teknisi->id_teknisi)->whereIn('status', ['Open', 'Proses', 'Pending'])->count(),
                'open_tickets_count' => TiketGangguan::whereNull('id_teknisi')->where('status', 'Open')->count(),
                'my_inventory_count' => \App\Models\InventoryItem::where('id_teknisi', $teknisi->id_teknisi)->count(),
                'total_routers' => Router::count(),
            ];

            // Assigned Tickets
            $myTickets = TiketGangguan::with('pelanggan')
                ->where('id_teknisi', $teknisi->id_teknisi)
                ->whereIn('status', ['Open', 'Proses', 'Pending'])
                ->latest()
                ->get();

            // Available tickets to grab/take over
            $availableTickets = TiketGangguan::with('pelanggan')
                ->whereNull('id_teknisi')
                ->where('status', 'Open')
                ->latest()
                ->get();

            // Optimize Route (specifically for technician's assigned tickets)
            $pelangganHigh = $myTickets->map(function($ticket) {
                $p = $ticket->pelanggan;
                if ($p) {
                    $p->prioritas_label = $ticket->prioritas;
                }
                return $p;
            })->filter()->values()->toArray();

            $routeService = app(\App\Services\RouteOptimizationService::class);
            $optimizedRoute = $routeService->optimize($teknisi->base_latitude, $teknisi->base_longitude, $pelangganHigh);
            
            // Sort myTickets based on the optimized route order
            $optimizedIdOrder = collect($optimizedRoute['route'])->pluck('id_pelanggan')->toArray();
            $myTickets = $myTickets->sortBy(function($ticket) use ($optimizedIdOrder) {
                $pos = array_search($ticket->id_pelanggan, $optimizedIdOrder);
                return $pos !== false ? $pos : 999;
            })->values();
            
            $routers = Router::all();
            
            // Sync all customer locations with status GIS (like admin dashboard)
            $pelangganMap = Pelanggan::with(['tagihan', 'tiket'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get()
                ->map(function($p) {
                    $status = 'online';
                    $hasTicket   = $p->tiket->whereIn('status', ['open', 'pending', 'proses'])->count() > 0;
                    $hasUnpaid   = $p->tagihan->whereIn('status', ['unpaid', 'belum_bayar'])->count() > 0;
                    $isOffline   = (!$p->last_online_status || $p->last_online_status === 'offline' || $p->last_online_status == 0);

                    if ($hasTicket)       $status = 'perbaikan';
                    elseif ($hasUnpaid)   $status = 'timeout';
                    elseif ($isOffline)   $status = 'offline';

                    $p->status_gis = $status;
                    return $p;
                });

            return view('content.dashboard.technician', compact('teknisi', 'stats', 'myTickets', 'availableTickets', 'optimizedRoute', 'routers', 'pelangganMap'));
        }

        // 1b. Dashboard Magang (Role ID 5)
        $isMagang = ($roleName === 'Magang' || $user->id_role == 5);
        if ($isMagang) {
            $tasks = \App\Models\InternTask::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return view('content.dashboard.intern', compact('tasks'));
        }

        // 2. Dashboard Pelanggan (Role ID 4)
        if ($user->id_role == 4) {
            $pelanggan = Pelanggan::where('id_user', $user->id)->first();
            
            if (!$pelanggan) {
                return view('content.dashboard.dashboard', ['error' => 'Data pelanggan tidak ditemukan.']);
            }

            $stats = [
                'total_tagihan' => $pelanggan->tagihan()->count(),
                'tagihan_unpaid' => $pelanggan->tagihan()->where('status', 'unpaid')->count(),
                'total_tiket' => $pelanggan->tiket()->count(),
                'tiket_open' => $pelanggan->tiket()->whereIn('status', ['open', 'pending', 'proses'])->count(),
            ];

            $recentTagihan = $pelanggan->tagihan()->latest()->take(5)->get();
            $recentTiket = $pelanggan->tiket()->latest()->take(5)->get();

            return view('content.dashboard.customer', compact('pelanggan', 'stats', 'recentTagihan', 'recentTiket'));
        }

        // 3. Dashboard Admin / Owner
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $total_pendapatan = \App\Models\Tagihan::where('status', 'paid')
            ->where(function($q) use ($currentMonth, $currentYear) {
                $q->where(function($sub) use ($currentMonth, $currentYear) {
                    $sub->where('bulan', $currentMonth)
                        ->where('tahun', $currentYear)
                        ->where('bayar_di_awal', false);
                })->orWhere(function($sub) use ($currentMonth, $currentYear) {
                    $sub->where('bayar_di_awal', true)
                        ->whereMonth('paid_at', $currentMonth)
                        ->whereYear('paid_at', $currentYear);
                });
            })->sum('jumlah');

        $total_pendapatan_cash = \App\Models\Tagihan::where('status', 'paid')
            ->where('metode_pembayaran', 'Cash')
            ->where(function($q) use ($currentMonth, $currentYear) {
                $q->where(function($sub) use ($currentMonth, $currentYear) {
                    $sub->where('bulan', $currentMonth)
                        ->where('tahun', $currentYear)
                        ->where('bayar_di_awal', false);
                })->orWhere(function($sub) use ($currentMonth, $currentYear) {
                    $sub->where('bayar_di_awal', true)
                        ->whereMonth('paid_at', $currentMonth)
                        ->whereYear('paid_at', $currentYear);
                });
            })->sum('jumlah');

        $total_pendapatan_transfer = \App\Models\Tagihan::where('status', 'paid')
            ->where('metode_pembayaran', '!=', 'Cash')
            ->where(function($q) use ($currentMonth, $currentYear) {
                $q->where(function($sub) use ($currentMonth, $currentYear) {
                    $sub->where('bulan', $currentMonth)
                        ->where('tahun', $currentYear)
                        ->where('bayar_di_awal', false);
                })->orWhere(function($sub) use ($currentMonth, $currentYear) {
                    $sub->where('bayar_di_awal', true)
                        ->whereMonth('paid_at', $currentMonth)
                        ->whereYear('paid_at', $currentYear);
                });
            })->sum('jumlah');

        $stats = [
            'total_pelanggan' => Pelanggan::count(),
            'total_gangguan' => TiketGangguan::where('status', 'Open')->count(),
            'gangguan_high' => TiketGangguan::where('prioritas', 'High')->where('status', 'Open')->count(),
            'total_teknisi' => Teknisi::count(),
            'total_router' => Router::count(),
            'tagihan_lunas' => \App\Models\Tagihan::where('status', 'paid')->where('bulan', $currentMonth)->where('tahun', $currentYear)->count(),
            'tagihan_unpaid' => \App\Models\Tagihan::where('status', 'unpaid')->where('bulan', $currentMonth)->where('tahun', $currentYear)->count(),
            'total_pendapatan' => $total_pendapatan,
            'total_pendapatan_cash' => $total_pendapatan_cash,
            'total_pendapatan_transfer' => $total_pendapatan_transfer,
            'total_tagihan_lunas' => \App\Models\Tagihan::where('status', 'paid')->count(),
            'total_tagihan_unpaid' => \App\Models\Tagihan::where('status', 'unpaid')->count(),
            'total_pengeluaran' => \App\Models\Keuangan::where('tipe', 'pengeluaran')->sum('jumlah'),
            'total_psb' => \App\Models\Keuangan::where('tipe', 'psb')->sum('jumlah'),
        ];

        // 4. Calculate dynamic Assets & Advance Advances (Kas Bon)
        $totalInventoryValue = \App\Models\InventoryItem::sum('harga_beli');
        $totalInventoryItems = \App\Models\InventoryItem::count();
        
        $totalKasBonOutstanding = 0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('kas_bon')) {
                $totalKasBonOutstanding = \App\Models\KasBon::where('status', 'belum_lunas')->sum('jumlah');
            }
        } catch (\Exception $e) {}

        $recentTiket = TiketGangguan::with('pelanggan')->latest()->take(5)->get();

        // Data peta sinkron dengan Web GIS Pelanggan
        $pelangganMap = Pelanggan::with(['tagihan', 'tiket'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function($p) {
                $status = 'online';
                $hasTicket   = $p->tiket->whereIn('status', ['open', 'pending', 'proses'])->count() > 0;
                $hasUnpaid   = $p->tagihan->whereIn('status', ['unpaid', 'belum_bayar'])->count() > 0;
                $isOffline   = (!$p->last_online_status || $p->last_online_status === 'offline' || $p->last_online_status == 0);

                if ($hasTicket)       $status = 'perbaikan';
                elseif ($hasUnpaid)   $status = 'timeout';
                elseif ($isOffline)   $status = 'offline';

                $p->status_gis = $status;
                return $p;
            });

        return view('content.dashboard.dashboard', compact('stats', 'recentTiket', 'pelangganMap', 'totalInventoryValue', 'totalInventoryItems', 'totalKasBonOutstanding'));
    }

    public function updateInternTaskStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:todo,progress,done',
        ]);

        $task = \App\Models\InternTask::findOrFail($id);

        // Authorization check: Intern can only edit their own tasks, Admin can edit any
        $user = auth()->user();
        $isAdmin = ($user->role && in_array($user->role->name, ['Admin', 'Manajer'])) || $user->id_role == 1;
        if (!$isAdmin && $task->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'task' => $task
        ]);
    }

    public function adminInternTasksIndex()
    {
        // Get all users with Role 'Magang' (id_role = 5)
        $interns = \App\Models\User::where('id_role', 5)->get();
        
        // Get all intern tasks with user profiles
        $tasks = \App\Models\InternTask::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('content.dashboard.admin_intern_tasks', compact('interns', 'tasks'));
    }

    public function adminStoreInternTask(Request $request)
    {
        $request->validate([
            'task' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
        ]);

        $task = \App\Models\InternTask::create([
            'user_id' => $request->user_id,
            'task' => $request->task,
            'status' => 'todo',
        ]);

        return response()->json([
            'status' => 'success',
            'task' => $task->load('user'),
        ]);
    }

    public function adminUpdateInternTask(Request $request, $id)
    {
        $request->validate([
            'task' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:todo,progress,done',
        ]);

        $task = \App\Models\InternTask::findOrFail($id);
        $task->update([
            'user_id' => $request->user_id,
            'task' => $request->task,
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'task' => $task->load('user'),
        ]);
    }

    public function adminDeleteInternTask($id)
    {
        $task = \App\Models\InternTask::findOrFail($id);
        $task->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Task deleted successfully',
        ]);
    }
}
