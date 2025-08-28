<?php

namespace App\Http\Controllers;

use App\Models\PesertaLotre;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('urutan_menang', 'asc')
            ->get();

        return response()->json($winners);
    }

    // Public API: pick a random non-winning participant and mark as next winner
    public function pick(Request $request)
    {
        // Use a transaction and FOR UPDATE lock to avoid races when multiple clients pick
        $picked = DB::transaction(function () {
            // select a random eligible participant and lock the row
            $candidate = PesertaLotre::where('apakah_menang', false)->lockForUpdate()->inRandomOrder()->first();
            if (!$candidate) {
                return null;
            }

            // next urutan_menang (start at 1)
            $max = PesertaLotre::whereNotNull('urutan_menang')->max('urutan_menang');
            $next = $max ? $max + 1 : 1;

            $candidate->apakah_menang = true;
            $candidate->urutan_menang = $next;
            $candidate->save();

            return $candidate;
        });

        if (!$picked) {
            return response()->json(['message' => 'No eligible participants'], 404);
        }

        return response()->json($picked);
    }

    // Public API: reset all winners (clear apakah_menang and urutan_menang)
    public function resetWinners(Request $request)
    {
        DB::transaction(function () {
            PesertaLotre::where('apakah_menang', true)->update([
                'apakah_menang' => false,
                'urutan_menang' => null,
            ]);
        });

        return response()->json(['reset' => true]);
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
