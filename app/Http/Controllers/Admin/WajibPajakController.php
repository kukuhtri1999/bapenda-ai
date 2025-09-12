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

    // Always render the Inertia page here. AJAX/listing is handled by `list()` API method.
    return inertia('WajibPajak/Index', [
      'wajibPajak' => $data
    ]);
  }

  // JSON-only endpoint for AJAX listing used by the front-end
  public function list(Request $request)
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

    return response()->json($data);
  }
}
