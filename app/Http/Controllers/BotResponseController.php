<?php

namespace App\Http\Controllers;

use App\Models\BotResponse;
use Illuminate\Http\Request;

class BotResponseController extends Controller
{
    public function index()
    {
        if (auth()->user()->id_role != 1) abort(403);
        $responses = BotResponse::with('parent')->latest()->get();
        $parentMenus = BotResponse::where('is_menu', true)->get();
        return view('content.bot.index', compact('responses', 'parentMenus'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->id_role != 1) abort(403);
        
        $request->validate([
            'keyword' => 'required|string',
            'response' => 'required|string',
            'parent_id' => 'nullable|exists:bot_responses,id',
        ]);

        BotResponse::create([
            'keyword' => strtolower($request->keyword),
            'menu_label' => $request->menu_label,
            'response' => $request->response,
            'is_exact_match' => $request->has('is_exact_match'),
            'is_active' => $request->has('is_active'),
            'is_menu' => $request->has('is_menu'),
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? 0,
            'group_enabled' => $request->has('group_enabled'),
        ]);

        return back()->with('success', 'Respon Bot berhasil ditambahkan!');
    }

    public function update(Request $request, BotResponse $bot)
    {
        if (auth()->user()->id_role != 1) abort(403);
        
        $request->validate([
            'keyword' => 'required|string',
            'response' => 'required|string',
            'parent_id' => 'nullable|exists:bot_responses,id',
        ]);

        $bot->update([
            'keyword' => strtolower($request->keyword),
            'menu_label' => $request->menu_label,
            'response' => $request->response,
            'is_exact_match' => $request->has('is_exact_match'),
            'is_active' => $request->has('is_active'),
            'is_menu' => $request->has('is_menu'),
            'parent_id' => $request->parent_id,
            'sort_order' => $request->sort_order ?? 0,
            'group_enabled' => $request->has('group_enabled'),
        ]);

        return back()->with('success', 'Respon Bot berhasil diperbarui!');
    }

    public function destroy(BotResponse $bot)
    {
        if (auth()->user()->id_role != 1) abort(403);
        
        // Gracefully set parent_id of any submenus to null to prevent FK constraint error
        BotResponse::where('parent_id', $bot->id)->update(['parent_id' => null]);
        
        $bot->delete();
        return back()->with('success', 'Respon Bot berhasil dihapus!');
    }
}
