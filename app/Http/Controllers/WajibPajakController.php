<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\WajibPajak;

class WajibPajakController extends Controller
{
    /**
     * Show wajib pajak form page (entry point for chat)
     */
    public function showForm()
    {
        // Check if user already has data in session
        $existingData = session('wajib_pajak_data');

        return inertia('WajibPajak/Form', [
            'existingData' => $existingData,
            'canProceedToChat' => !empty($existingData),
            'cms' => \App\Models\HomepageContent::getAllGrouped(),
        ]);
    }

    /**
     * Start chat session - save user data to session and redirect to chat
     */
    public function startChatSession(Request $request)
    {
        // ── 1. Honeypot check: reject automated spambots filling hidden field ──
        if (!empty($request->input('website_verification'))) {
            Log::warning('Honeypot caught bot spam on Wajib Pajak submission from IP: ' . $request->ip());
            return response()->json([
                'success' => false,
                'message' => 'Validasi keamanan formulir gagal.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'nopol' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z]{1,2}\s?\d{1,4}\s?[A-Za-z]{0,3}$/'
            ],
            'nomer_wa' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+62|62|0)8[1-9][0-9]{6,11}$/'
            ],
        ], [
            'nama.required' => 'Nama wajib diisi',
            'nama.max' => 'Nama tidak boleh lebih dari 255 karakter',
            'nopol.required' => 'Nomor polisi wajib diisi',
            'nopol.max' => 'Nomor polisi tidak boleh lebih dari 20 karakter',
            'nopol.regex' => 'Format nomor polisi tidak valid (contoh: B 1234 CD)',
            'nomer_wa.required' => 'Nomor WhatsApp wajib diisi',
            'nomer_wa.max' => 'Nomor WhatsApp tidak boleh lebih dari 20 karakter',
            'nomer_wa.regex' => 'Format nomor WhatsApp tidak valid (contoh: 081234567890 atau +6281234567890)',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Store in session for middleware access
        $wajibPajakData = [
            'nama' => $request->nama,
            'nopol' => strtoupper($request->nopol),
            'nomer_wa' => $request->nomer_wa,
        ];

        // Save to database permanently for bank data
        try {
            // Check if user already exists by nopol
            $existingWajibPajak = WajibPajak::where('nopol', strtoupper($request->nopol))->first();

            if ($existingWajibPajak) {
                // Update existing data
                $existingWajibPajak->update($wajibPajakData);
            } else {
                // Create new record
                WajibPajak::create($wajibPajakData);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the session creation
            Log::error('Failed to save wajib pajak data: ' . $e->getMessage());
        }

        session(['wajib_pajak_data' => $wajibPajakData]);
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan, anda dapat mengakses chat AI',
            'redirect' => route('customer-service')
        ]);
    }

    /**
     * Clear session data (logout from chat)
     */
    public function clearSession()
    {
        session()->forget('wajib_pajak_data');

        return response()->json([
            'success' => true,
            'message' => 'Session berhasil dibersihkan'
        ]);
    }

    /**
     * Check if user has wajib pajak session data
     */
    public function checkSession()
    {
        $hasSession = session()->has('wajib_pajak_data');
        $sessionData = $hasSession ? session('wajib_pajak_data') : null;

        return response()->json([
            'hasSession' => $hasSession,
            'data' => $sessionData
        ]);
    }
}
