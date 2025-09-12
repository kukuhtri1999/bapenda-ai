<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WajibPajak;

class WajibPajakController extends Controller
{
  public function index(Request $request)
  {
    $q = $request->query('q');
    $perPage = intval($request->query('per_page', 15));

    $query = WajibPajak::query();

    if ($q) {
      $query->where(function ($qwhere) use ($q) {
        $qwhere->where('nama', 'like', "%{$q}%")
          ->orWhere('nopol', 'like', "%{$q}%")
          ->orWhere('nomer_wa', 'like', "%{$q}%");
      });
    }

    $data = $query->orderByDesc('created_at')->paginate($perPage)->appends($request->query());

    // If AJAX or expects JSON, return lightweight JSON paginator for live search
    if ($request->ajax() || $request->wantsJson()) {
      return response()->json($data);
    }

    return inertia('WajibPajak/Index', [
      'wajibPajak' => $data
    ]);
  }
}
