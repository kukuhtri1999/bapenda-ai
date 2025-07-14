<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PkbScrapingService;
use App\Models\WajibPajak;
use App\Models\DataPkb;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PkbController extends Controller
{
    protected $pkbScrapingService;

    public function __construct(PkbScrapingService $pkbScrapingService)
    {
        $this->pkbScrapingService = $pkbScrapingService;
    }

    /**
     * Show PKB check form
     */
    public function index()
    {
        return inertia('Pkb/Index');
    }

    /**
     * Start PKB checking process
     */
    public function check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'nopol' => 'required|string|max:20',
            'lima_digit_terakhir_no_rangka' => 'required|string|size:5',
            'nomer_wa' => 'required|string|max:20',
            'g-recaptcha-response' => 'required'
        ], [
            'nama.required' => 'Nama wajib diisi',
            'nopol.required' => 'Nomor polisi wajib diisi',
            'lima_digit_terakhir_no_rangka.required' => '5 digit terakhir nomor rangka wajib diisi',
            'lima_digit_terakhir_no_rangka.size' => '5 digit terakhir nomor rangka harus tepat 5 digit',
            'nomer_wa.required' => 'Nomor WhatsApp wajib diisi',
            'g-recaptcha-response.required' => 'reCAPTCHA wajib diverifikasi'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $wajibPajakData = [
            'nama' => $request->nama,
            'nopol' => strtoupper($request->nopol),
            'lima_digit_terakhir_no_rangka' => $request->lima_digit_terakhir_no_rangka,
            'nomer_wa' => $request->nomer_wa
        ];

        $result = $this->pkbScrapingService->checkPkbWithCaptchaFlow($wajibPajakData, $request->input('g-recaptcha-response'));

        return response()->json($result);
    }

    /**
     * Save captcha answer (async function)
     */
    public function saveCaptchaAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'captcha_answer' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->pkbScrapingService->saveCaptchaAnswer($request->captcha_answer);
        return response()->json($result);
    }

    /**
     * Submit captcha and continue PKB check
     */
    public function submitCaptcha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wajib_pajak_id' => 'required|exists:wajib_pajak,id',
            'captcha_answer' => 'required|string',
            'session_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->pkbScrapingService->continuePkbCheck(
            $request->wajib_pajak_id,
            $request->captcha_answer,
            $request->session_id
        );

        return response()->json($result);
    }

    /**
     * Confirm PKB data
     */
    public function confirmData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_pkb_id' => 'required|exists:data_pkb,id',
            'is_correct' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $dataPkb = DataPkb::with(['wajibPajak', 'tambahanBiaya'])->find($request->data_pkb_id);

            if (!$request->is_correct) {
                // User said data is incorrect, soft delete everything
                $this->pkbScrapingService->deletePkbData($request->data_pkb_id);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Data telah dihapus karena tidak sesuai'
                ]);
            }

            // User confirmed data is correct
            // Prepare data for AI chat context
            $chatContext = $this->prepareChatContext($dataPkb);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data PKB berhasil dikonfirmasi',
                'chat_context' => $chatContext
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    /**
     * Get PKB data for display
     */
    public function getPkbData($id)
    {
        try {
            $dataPkb = DataPkb::with(['wajibPajak', 'tambahanBiaya'])->find($id);

            if (!$dataPkb) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data PKB tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $dataPkb
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem'
            ], 500);
        }
    }

    /**
     * Prepare chat context from PKB data
     */
    private function prepareChatContext($dataPkb)
    {
        $context = "=== INFORMASI KENDARAAN USER ===\n";
        $context .= "Nama Pemilik: {$dataPkb->wajibPajak->nama}\n";
        $context .= "Nomor Polisi: {$dataPkb->nopol}\n";
        $context .= "Nomor WhatsApp: {$dataPkb->wajibPajak->nomer_wa}\n";
        $context .= "Warna: {$dataPkb->warna}\n";
        $context .= "Merk: {$dataPkb->merk}\n";
        $context .= "Model: {$dataPkb->model}\n";
        $context .= "Type: {$dataPkb->type}\n";
        $context .= "Tahun: {$dataPkb->tahun}\n";
        $context .= "Masa Pajak: {$dataPkb->tanggal_masa_pajak}\n\n";

        $context .= "=== INFORMASI PAJAK ===\n";
        $context .= "PKB: Rp " . number_format($dataPkb->pkb, 0, ',', '.') . "\n";
        $context .= "Opsen PKB: Rp " . number_format($dataPkb->opsen_pkb, 0, ',', '.') . "\n";

        if ($dataPkb->pkb_progresif > 0) {
            $context .= "PKB Progresif: Rp " . number_format($dataPkb->pkb_progresif, 0, ',', '.') . "\n";
            $context .= "Opsen PKB Progresif: Rp " . number_format($dataPkb->opsen_pkb_prog, 0, ',', '.') . "\n";
        }

        $context .= "SWDKLLJ: Rp " . number_format($dataPkb->swdkllj, 0, ',', '.') . "\n";
        $context .= "Parkir Berlangganan: Rp " . number_format($dataPkb->parkir_berlangganan, 0, ',', '.') . "\n";
        $context .= "Pengesahan STNK: Rp " . number_format($dataPkb->pengesahan_stnk, 0, ',', '.') . "\n";

        if ($dataPkb->tambahanBiaya->count() > 0) {
            $context .= "\n=== TAMBAHAN BIAYA ===\n";
            foreach ($dataPkb->tambahanBiaya as $biaya) {
                $context .= "{$biaya->label_biaya}: Rp " . number_format($biaya->harga_biaya, 0, ',', '.') . "\n";
            }
        }

        $context .= "\nTOTAL: Rp " . number_format($dataPkb->total, 0, ',', '.') . "\n";
        $context .= "\nSilakan bertanya tentang informasi pajak kendaraan Anda di atas.";

        return $context;
    }
}
