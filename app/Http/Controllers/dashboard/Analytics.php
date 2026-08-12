<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Analytics extends Controller
{
  public function index()
  {
    $totalInventoryValue = \App\Models\InventoryItem::sum('harga_beli');
    $totalInventoryItems = \App\Models\InventoryItem::count();

    return view('content.dashboard.dashboards-analytics', compact('totalInventoryValue', 'totalInventoryItems'));
  }
}
