<?php

namespace App\Http\Controllers;

use App\Models\PesertaLotre;
use App\Models\LotreSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LotreController extends Controller
{
    // Public page
    public function index()
    {
        $totalParticipantsCount = PesertaLotre::count();

        // Build a balanced stratified sample across all kecamatans so the slot reel
        // displays all subdistricts fairly (even when 45k+ rows are sorted by kecamatan in Excel)
        $kecamatans = PesertaLotre::where('apakah_menang', false)
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->distinct()
            ->pluck('kecamatan');

        $participants = collect();
        if ($kecamatans->isNotEmpty()) {
            $perKec = max(4, (int) ceil(220 / $kecamatans->count()));
            foreach ($kecamatans as $kec) {
                $sample = PesertaLotre::select([
                    'id', 'nama', 'nopol', 'alamat', 'kecamatan', 'apakah_menang', 'urutan_menang',
                ])
                    ->where('apakah_menang', false)
                    ->where('kecamatan', $kec)
                    ->limit($perKec)
                    ->get();
                $participants = $participants->concat($sample);
            }
            $participants = $participants->shuffle()->values();
        } else {
            $participants = PesertaLotre::select([
                'id', 'nama', 'nopol', 'alamat', 'kecamatan', 'apakah_menang', 'urutan_menang',
            ])->where('apakah_menang', false)->limit(150)->get()->shuffle()->values();
        }

        $winners = PesertaLotre::select([
            'id', 'nama', 'nopol', 'alamat', 'kecamatan', 'urutan_menang',
        ])->whereNotNull('urutan_menang')->orderBy('urutan_menang', 'asc')->get();

        // Pass settings and initial data directly to avoid extra client-side API calls
        return Inertia::render('Lotre/Index', [
            'spinDuration' => LotreSetting::getSpinDuration(),
            'totalParticipantsCount' => $totalParticipantsCount,
            'initialParticipants' => $participants,
            'initialWinners' => $winners,
        ]);
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
        $lotreMode = LotreSetting::getLotreMode();

        $picked = DB::transaction(function () use ($lotreMode) {
            // Determine next urutan_menang (start at 1)
            $maxUrutan = PesertaLotre::whereNotNull('urutan_menang')->max('urutan_menang');
            $nextUrutan = $maxUrutan ? $maxUrutan + 1 : 1;

            $candidate = null;

            if ($lotreMode === 'custom') {
                // Custom mode: check if there's a predetermined winner for this position
                $candidate = PesertaLotre::where('apakah_menang', false)
                    ->where('predetermined_winner_order', $nextUrutan)
                    ->lockForUpdate()
                    ->first();

                // If no predetermined winner for this position, or they already won,
                // check if there are any predetermined winners left that haven't won
                if (!$candidate) {
                    $candidate = PesertaLotre::where('apakah_menang', false)
                        ->whereNotNull('predetermined_winner_order')
                        ->orderBy('predetermined_winner_order', 'asc')
                        ->lockForUpdate()
                        ->first();
                }

                // If still no predetermined winner available, fall back to random
                if (!$candidate) {
                    $candidate = $this->pickRandomCandidate(
                        PesertaLotre::where('apakah_menang', false)
                    );
                }
            } elseif ($lotreMode === 'one_per_kecamatan') {
                // One Per Kecamatan mode: exclude kecamatans that already have a winner,
                // then pick one remaining kecamatan uniformly at random so all kecamatans have equal odds
                $wonKecamatans = PesertaLotre::where('apakah_menang', true)
                    ->whereNotNull('kecamatan')
                    ->where('kecamatan', '!=', '')
                    ->distinct()
                    ->pluck('kecamatan')
                    ->toArray();

                $eligibleKecamatansQuery = PesertaLotre::where('apakah_menang', false)
                    ->whereNotNull('kecamatan')
                    ->where('kecamatan', '!=', '');

                if (!empty($wonKecamatans)) {
                    $eligibleKecamatansQuery->whereNotIn('kecamatan', $wonKecamatans);
                }

                $eligibleKecamatans = $eligibleKecamatansQuery->distinct()->pluck('kecamatan')->all();

                if (!empty($eligibleKecamatans)) {
                    $chosenKecamatan = $eligibleKecamatans[random_int(0, count($eligibleKecamatans) - 1)];
                    $candidate = $this->pickRandomCandidate(
                        PesertaLotre::where('apakah_menang', false)->where('kecamatan', $chosenKecamatan)
                    );
                }

                // If no more eligible kecamatans, check if there are participants without kecamatan
                if (!$candidate) {
                    $candidate = $this->pickRandomCandidate(
                        PesertaLotre::where('apakah_menang', false)
                            ->where(function ($q) {
                                $q->whereNull('kecamatan')
                                    ->orWhere('kecamatan', '');
                            })
                    );
                }
            } else {
                // Random mode: select a random eligible participant
                $candidate = $this->pickRandomCandidate(
                    PesertaLotre::where('apakah_menang', false)
                );
            }

            if (!$candidate) {
                return null;
            }

            $candidate->apakah_menang = true;
            $candidate->urutan_menang = $nextUrutan;
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

    /**
     * Pick a single random row using indexed count + offset instead of slow ORDER BY RAND().
     */
    private function pickRandomCandidate($query): ?PesertaLotre
    {
        $count = (clone $query)->count();
        if ($count === 0) {
            return null;
        }

        $offset = random_int(0, $count - 1);

        return $query->lockForUpdate()->offset($offset)->limit(1)->first();
    }
}
