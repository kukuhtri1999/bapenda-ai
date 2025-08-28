<?php

namespace App\Http\Controllers;

use App\Models\PesertaLotre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LotreController extends Controller
{
    // Public page
    public function index()
    {
        // We'll load minimal props and let the page call the API
        return Inertia::render('Lotre/Index');
    }

    // Public API: list participants (paginated)
    public function list(Request $request)
    {
        $perPage = (int) $request->get('per_page', 50);
        $participants = PesertaLotre::orderBy('id')->paginate($perPage);

        return response()->json($participants);
    }

    // Public API: get predetermined winners ordered by urutan_menang
    public function winners(Request $request)
    {
        $winners = PesertaLotre::whereNotNull('urutan_menang')
            ->orderBy('urutan_menang')
            ->get();

        return response()->json($winners);
    }

    // Admin-ish endpoint: quick create participant (public allowed)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'nullable|string|max:255',
            'nopol' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $p = PesertaLotre::create($validator->validated());

        return response()->json($p, 201);
    }
}
