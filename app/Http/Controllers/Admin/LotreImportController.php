<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PesertaLotreImport;
use App\Models\PesertaLotre;
use App\Models\LotreSetting;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LotreImportController extends Controller
{
  /**
   * Display the import page
   */
  public function index(): Response
  {
    // Get current statistics
    $totalParticipants = PesertaLotre::count();
    $totalWinners = PesertaLotre::where('apakah_menang', true)->count();

    return Inertia::render('Admin/LotreManagement/Import', [
      'stats' => [
        'total_participants' => $totalParticipants,
        'total_winners' => $totalWinners,
      ],
    ]);
  }

  /**
   * Display the settings page
   */
  public function settings(): Response
  {
    $totalParticipants = PesertaLotre::count();
    $totalWinners = PesertaLotre::where('apakah_menang', true)->count();
    $predeterminedWinners = PesertaLotre::predeterminedWinners()->get();

    return Inertia::render('Admin/LotreManagement/Settings', [
      'stats' => [
        'total_participants' => $totalParticipants,
        'total_winners' => $totalWinners,
      ],
      'settings' => [
        'lotre_mode' => LotreSetting::getLotreMode(),
        'spin_duration_ms' => LotreSetting::getSpinDuration(),
      ],
      'predeterminedWinners' => $predeterminedWinners,
    ]);
  }

  /**
   * Update lotre settings
   */
  public function updateSettings(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'lotre_mode' => 'required|in:random,custom,one_per_kecamatan',
      'spin_duration_ms' => 'required|integer|min:1000|max:15000',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      LotreSetting::setValue('lotre_mode', $request->lotre_mode, 'string', 'Mode lotre: random atau custom');
      LotreSetting::setValue('spin_duration_ms', $request->spin_duration_ms, 'integer', 'Durasi animasi spin dalam milidetik');

      return response()->json([
        'success' => true,
        'message' => 'Pengaturan berhasil disimpan.',
      ]);
    } catch (\Throwable $e) {
      Log::error('Update lotre settings failed: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Gagal menyimpan pengaturan.',
      ], 500);
    }
  }

  /**
   * Search participants for winner picker
   */
  public function searchParticipants(Request $request): JsonResponse
  {
    $query = $request->get('q', '');

    if (strlen($query) < 2) {
      return response()->json([]);
    }

    $participants = PesertaLotre::where(function ($q) use ($query) {
      $q->where('nama', 'like', "%{$query}%")
        ->orWhere('nopol', 'like', "%{$query}%");
    })
      ->limit(20)
      ->get(['id', 'nama', 'nopol', 'alamat', 'predetermined_winner_order']);

    return response()->json($participants);
  }

  /**
   * Set predetermined winners
   */
  public function setPredeterminedWinners(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'winners' => 'required|array',
      'winners.*.id' => 'required|exists:peserta_lotre,id',
      'winners.*.order' => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      DB::transaction(function () use ($request) {
        // Clear all existing predetermined orders
        PesertaLotre::whereNotNull('predetermined_winner_order')
          ->update(['predetermined_winner_order' => null]);

        // Set new predetermined orders
        foreach ($request->winners as $winner) {
          PesertaLotre::where('id', $winner['id'])
            ->update(['predetermined_winner_order' => $winner['order']]);
        }
      });

      $predeterminedWinners = PesertaLotre::predeterminedWinners()->get();

      return response()->json([
        'success' => true,
        'message' => 'Pemenang berhasil ditentukan.',
        'predeterminedWinners' => $predeterminedWinners,
      ]);
    } catch (\Throwable $e) {
      Log::error('Set predetermined winners failed: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Gagal menyimpan pemenang.',
      ], 500);
    }
  }

  /**
   * Clear all predetermined winners
   */
  public function clearPredeterminedWinners(): JsonResponse
  {
    try {
      PesertaLotre::whereNotNull('predetermined_winner_order')
        ->update(['predetermined_winner_order' => null]);

      return response()->json([
        'success' => true,
        'message' => 'Semua pemenang yang ditentukan telah dihapus.',
      ]);
    } catch (\Throwable $e) {
      Log::error('Clear predetermined winners failed: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Gagal menghapus pemenang.',
      ], 500);
    }
  }

  /**
   * Preview the first 10 rows of the uploaded file without importing
   */
  public function preview(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'file' => 'required|file|mimes:xlsx,xls,csv|max:51200',
    ], [
      'file.required' => 'File harus diunggah.',
      'file.file' => 'Upload harus berupa file.',
      'file.mimes' => 'Format file harus XLSX, XLS, atau CSV.',
      'file.max' => 'Ukuran file maksimal 50MB.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      @set_time_limit(180);
      @ini_set('memory_limit', '512M');

      $file = $request->file('file');
      $reader = IOFactory::createReaderForFile($file->getPathname());
      if (method_exists($reader, 'setReadDataOnly')) {
        $reader->setReadDataOnly(true);
      }
      $spreadsheet = $reader->load($file->getPathname());
      $sheet = $spreadsheet->getActiveSheet();
      $rows = $sheet->toArray();

      if (empty($rows)) {
        return response()->json([
          'success' => false,
          'message' => 'File kosong atau tidak dapat dibaca.',
        ], 422);
      }

      // Get headers from first row
      $headers = array_map(function ($h) {
        return strtolower(trim((string) $h));
      }, $rows[0]);

      // Map column indices
      $namaIdx = $this->findColumnIndex($headers, ['nama', 'name', 'nama_peserta', 'peserta']);
      $nopolIdx = $this->findColumnIndex($headers, ['nopol', 'no_pol', 'nomor_polisi', 'plat', 'plat_nomor']);
      $alamatIdx = $this->findColumnIndex($headers, ['alamat', 'address', 'alamat_peserta']);
      $kecamatanIdx = $this->findColumnIndex($headers, ['kecamatan', 'kec', 'district', 'wilayah']);

      // Check if we can identify at least one required column
      if ($namaIdx === null && $nopolIdx === null) {
        return response()->json([
          'success' => false,
          'message' => 'Kolom "nama" atau "nopol" tidak ditemukan. Pastikan header sesuai format.',
          'detected_headers' => $headers,
        ], 422);
      }

      // Extract preview data (first 10 data rows, skip header)
      $previewData = [];
      $totalRows = count($rows) - 1; // Exclude header
      $previewCount = min(10, $totalRows);

      for ($i = 1; $i <= $previewCount; $i++) {
        $row = $rows[$i];
        $nama = $namaIdx !== null ? trim((string) ($row[$namaIdx] ?? '')) : '';
        $nopol = $nopolIdx !== null ? strtoupper(trim((string) ($row[$nopolIdx] ?? ''))) : '';
        $alamat = $alamatIdx !== null ? trim((string) ($row[$alamatIdx] ?? '')) : '';
        $kecamatan = $kecamatanIdx !== null ? trim((string) ($row[$kecamatanIdx] ?? '')) : '';

        // Skip completely empty rows
        if (empty($nama) && empty($nopol) && empty($alamat) && empty($kecamatan)) {
          continue;
        }

        $previewData[] = [
          'row' => $i + 1, // 1-indexed, +1 for header
          'nama' => $nama,
          'nopol' => $nopol,
          'alamat' => $alamat,
          'kecamatan' => $kecamatan,
          'valid' => !empty($nama) || !empty($nopol),
        ];
      }

      return response()->json([
        'success' => true,
        'total_rows' => $totalRows,
        'preview_count' => count($previewData),
        'detected_columns' => [
          'nama' => $namaIdx !== null ? $headers[$namaIdx] : null,
          'nopol' => $nopolIdx !== null ? $headers[$nopolIdx] : null,
          'alamat' => $alamatIdx !== null ? $headers[$alamatIdx] : null,
          'kecamatan' => $kecamatanIdx !== null ? $headers[$kecamatanIdx] : null,
        ],
        'preview' => $previewData,
      ]);
    } catch (\Throwable $e) {
      Log::error('Preview failed: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Gagal membaca file: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Find column index by possible header names
   */
  private function findColumnIndex(array $headers, array $possibleNames): ?int
  {
    foreach ($possibleNames as $name) {
      $idx = array_search($name, $headers);
      if ($idx !== false) {
        return $idx;
      }
    }
    return null;
  }

  /**
   * Handle the file upload and import
   */
  public function upload(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'file' => 'required|file|mimes:xlsx,xls,csv|max:20480', // up to 20MB
    ], [
      'file.required' => 'File harus diunggah.',
      'file.file' => 'Upload harus berupa file.',
      'file.mimes' => 'Format file harus XLSX, XLS, atau CSV.',
      'file.max' => 'Ukuran file maksimal 20MB.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      $import = new PesertaLotreImport();
      Excel::import($import, $request->file('file'));

      return response()->json([
        'success' => true,
        'imported' => $import->getImportedCount(),
        'skipped' => $import->getSkippedCount(),
        'duplicates' => $import->getDuplicatesCount(),
        'errors' => $import->getErrors(),
        'stats' => [
          'total_participants' => PesertaLotre::count(),
          'total_winners' => PesertaLotre::where('apakah_menang', true)->count(),
        ],
      ]);
    } catch (\Throwable $e) {
      Log::error('PesertaLotre import failed: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Import gagal: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Step 1 of chunked big-data import:
   * Parse uploaded spreadsheet once (read-data-only), normalize rows,
   * and split into 1,000-row JSON chunks for fast part-by-part insertion.
   */
  public function prepareImport(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'file' => 'required|file|mimes:xlsx,xls,csv|max:51200',
    ], [
      'file.required' => 'File harus diunggah.',
      'file.file' => 'Upload harus berupa file.',
      'file.mimes' => 'Format file harus XLSX, XLS, atau CSV.',
      'file.max' => 'Ukuran file maksimal 50MB.',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      @set_time_limit(300);
      @ini_set('memory_limit', '512M');

      $file = $request->file('file');
      $reader = IOFactory::createReaderForFile($file->getPathname());
      if (method_exists($reader, 'setReadDataOnly')) {
        $reader->setReadDataOnly(true);
      }
      $spreadsheet = $reader->load($file->getPathname());
      $sheet = $spreadsheet->getActiveSheet();
      $rows = $sheet->toArray();

      // Free PhpSpreadsheet memory immediately
      $spreadsheet->disconnectWorksheets();
      unset($spreadsheet, $sheet);

      if (count($rows) < 2) {
        return response()->json([
          'success' => false,
          'message' => 'File kosong atau tidak memiliki baris data.',
        ], 422);
      }

      $headers = array_map(function ($h) {
        return strtolower(trim((string) $h));
      }, $rows[0]);

      $namaIdx = $this->findColumnIndex($headers, ['nama', 'name', 'nama_peserta', 'peserta']);
      $nopolIdx = $this->findColumnIndex($headers, ['nopol', 'no_pol', 'nomor_polisi', 'plat', 'plat_nomor']);
      $alamatIdx = $this->findColumnIndex($headers, ['alamat', 'address', 'alamat_peserta']);
      $kecamatanIdx = $this->findColumnIndex($headers, ['kecamatan', 'kec', 'district', 'wilayah']);

      if ($namaIdx === null && $nopolIdx === null) {
        return response()->json([
          'success' => false,
          'message' => 'Kolom "nama" atau "nopol" tidak ditemukan.',
        ], 422);
      }

      $importId = 'imp_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6));
      $dirPath = storage_path("app/lotre_imports/{$importId}");
      if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
      }

      $chunkSize = 1000;
      $currentChunk = [];
      $chunkIndex = 0;
      $totalRows = 0;
      $totalCount = count($rows);

      for ($i = 1; $i < $totalCount; $i++) {
        $row = $rows[$i];
        $nama = $namaIdx !== null ? trim((string) ($row[$namaIdx] ?? '')) : '';
        $nopol = $nopolIdx !== null ? strtoupper(trim((string) ($row[$nopolIdx] ?? ''))) : '';
        $alamat = $alamatIdx !== null ? trim((string) ($row[$alamatIdx] ?? '')) : '';
        $kecamatan = $kecamatanIdx !== null ? trim((string) ($row[$kecamatanIdx] ?? '')) : '';

        // Skip completely empty trailing rows
        if ($nama === '' && $nopol === '' && $alamat === '' && $kecamatan === '') {
          continue;
        }

        $currentChunk[] = [
          'row' => $i + 1,
          'nama' => $nama !== '' ? $nama : null,
          'nopol' => $nopol !== '' ? $nopol : null,
          'alamat' => $alamat !== '' ? $alamat : null,
          'kecamatan' => $kecamatan !== '' ? $kecamatan : null,
        ];
        $totalRows++;

        if (count($currentChunk) >= $chunkSize) {
          file_put_contents(
            "{$dirPath}/chunk_{$chunkIndex}.json",
            json_encode($currentChunk, JSON_UNESCAPED_UNICODE)
          );
          $chunkIndex++;
          $currentChunk = [];
        }
      }

      if (!empty($currentChunk)) {
        file_put_contents(
          "{$dirPath}/chunk_{$chunkIndex}.json",
          json_encode($currentChunk, JSON_UNESCAPED_UNICODE)
        );
        $chunkIndex++;
      }

      unset($rows, $currentChunk);

      if ($totalRows === 0) {
        $this->deleteImportDirectory($dirPath);
        return response()->json([
          'success' => false,
          'message' => 'Tidak ada data valid yang ditemukan di dalam file.',
        ], 422);
      }

      return response()->json([
        'success' => true,
        'import_id' => $importId,
        'total_rows' => $totalRows,
        'total_chunks' => $chunkIndex,
        'chunk_size' => $chunkSize,
      ]);
    } catch (\Throwable $e) {
      Log::error('Prepare import failed: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Gagal memproses file: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Step 2 of chunked big-data import:
   * Process a single 1,000-row chunk using batch duplicate check + batch insert.
   */
  public function processImportChunk(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'import_id' => ['required', 'string', 'regex:/^[a-zA-Z0-9_\-]+$/'],
      'chunk_index' => 'required|integer|min:0',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    $importId = $request->input('import_id');
    $chunkIndex = (int) $request->input('chunk_index');
    $dirPath = storage_path("app/lotre_imports/{$importId}");
    $chunkFile = "{$dirPath}/chunk_{$chunkIndex}.json";

    if (!file_exists($chunkFile)) {
      return response()->json([
        'success' => false,
        'message' => "Chunk #{$chunkIndex} tidak ditemukan atau sudah diproses.",
      ], 404);
    }

    try {
      $rows = json_decode(file_get_contents($chunkFile), true) ?: [];
      $now = now()->toDateTimeString();

      $imported = 0;
      $skipped = 0;
      $duplicates = 0;
      $errors = [];

      DB::transaction(function () use ($rows, $now, &$imported, &$skipped, &$duplicates, &$errors) {
        // 1. Batch lookup all non-empty nopols in this chunk in ONE query
        $nopols = [];
        foreach ($rows as $r) {
          if (!empty($r['nopol'])) {
            $nopols[] = $r['nopol'];
          }
        }
        $nopols = array_values(array_unique($nopols));

        $existingNopols = [];
        if (!empty($nopols)) {
          $existingNopols = PesertaLotre::whereIn('nopol', $nopols)
            ->pluck('nopol')
            ->flip()
            ->all();
        }

        $seenInChunk = [];
        $insertBatch = [];

        foreach ($rows as $r) {
          $rowNum = $r['row'] ?? '?';
          $nama = $r['nama'] ?? null;
          $nopol = $r['nopol'] ?? null;

          if (empty($nama) && empty($nopol)) {
            $skipped++;
            if (count($errors) < 50) {
              $errors[] = "Baris {$rowNum}: nama dan nopol kosong.";
            }
            continue;
          }

          if (!empty($nopol)) {
            if (isset($existingNopols[$nopol]) || isset($seenInChunk[$nopol])) {
              $duplicates++;
              $skipped++;
              if (count($errors) < 50) {
                $errors[] = "Baris {$rowNum}: nopol '{$nopol}' duplikat, dilewati.";
              }
              continue;
            }
            $seenInChunk[$nopol] = true;
          }

          $insertBatch[] = [
            'nama' => $nama,
            'nopol' => $nopol,
            'alamat' => $r['alamat'] ?? null,
            'kecamatan' => $r['kecamatan'] ?? null,
            'apakah_menang' => false,
            'urutan_menang' => null,
            'created_at' => $now,
            'updated_at' => $now,
          ];
          $imported++;
        }

        // 2. Batch insert in 500-row groups (2 fast multi-row INSERTs for 1,000 rows)
        if (!empty($insertBatch)) {
          foreach (array_chunk($insertBatch, 500) as $subBatch) {
            PesertaLotre::insert($subBatch);
          }
        }
      });

      // Remove processed chunk file & cleanup directory if empty
      @unlink($chunkFile);
      $remaining = glob("{$dirPath}/chunk_*.json");
      if (empty($remaining)) {
        $this->deleteImportDirectory($dirPath);
      }

      return response()->json([
        'success' => true,
        'chunk_index' => $chunkIndex,
        'processed_in_chunk' => count($rows),
        'imported' => $imported,
        'skipped' => $skipped,
        'duplicates' => $duplicates,
        'errors' => $errors,
        'stats' => [
          'total_participants' => PesertaLotre::count(),
          'total_winners' => PesertaLotre::where('apakah_menang', true)->count(),
        ],
      ]);
    } catch (\Throwable $e) {
      Log::error("Process chunk #{$chunkIndex} failed: " . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => "Gagal memproses part #{$chunkIndex}: " . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Cleanup temporary chunk files if user cancels mid-import.
   */
  public function cleanupImport(Request $request): JsonResponse
  {
    $importId = $request->input('import_id', '');
    if (preg_match('/^[a-zA-Z0-9_\-]+$/', $importId)) {
      $dirPath = storage_path("app/lotre_imports/{$importId}");
      $this->deleteImportDirectory($dirPath);
    }

    return response()->json([
      'success' => true,
      'stats' => [
        'total_participants' => PesertaLotre::count(),
        'total_winners' => PesertaLotre::where('apakah_menang', true)->count(),
      ],
    ]);
  }

  /**
   * Safely remove temporary import directory.
   */
  private function deleteImportDirectory(string $dirPath): void
  {
    if (!is_dir($dirPath)) {
      return;
    }

    $files = glob("{$dirPath}/*");
    if ($files) {
      foreach ($files as $file) {
        if (is_file($file)) {
          @unlink($file);
        }
      }
    }
    @rmdir($dirPath);
  }

  /**
   * Clear all participant data (with confirmation in frontend)
   */
  public function clearAll(Request $request): JsonResponse
  {
    try {
      $count = PesertaLotre::count();
      PesertaLotre::truncate();

      return response()->json([
        'success' => true,
        'message' => "Berhasil menghapus {$count} data peserta.",
        'deleted' => $count,
      ]);
    } catch (\Throwable $e) {
      Log::error('PesertaLotre clear all failed: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'Gagal menghapus data: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Download sample/template file
   */
  public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
  {
    $templatePath = storage_path('app/templates/peserta_lotre_template.xlsx');

    // If template doesn't exist, create one dynamically
    if (!file_exists($templatePath)) {
      $this->createTemplate($templatePath);
    }

    return response()->download($templatePath, 'template_peserta_lotre.xlsx');
  }

  /**
   * Create a template file with headers and sample data
   */
  private function createTemplate(string $path): void
  {
    $directory = dirname($path);
    if (!is_dir($directory)) {
      mkdir($directory, 0755, true);
    }

    // For xlsx, we'll use PhpSpreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'nama');
    $sheet->setCellValue('B1', 'nopol');
    $sheet->setCellValue('C1', 'alamat');
    $sheet->setCellValue('D1', 'kecamatan');

    // Set sample data
    $sheet->setCellValue('A2', 'John Doe');
    $sheet->setCellValue('B2', 'AB 1234 CD');
    $sheet->setCellValue('C2', 'Jl. Contoh No. 1');
    $sheet->setCellValue('D2', 'Kediri');

    $sheet->setCellValue('A3', 'Jane Smith');
    $sheet->setCellValue('B3', 'EF 5678 GH');
    $sheet->setCellValue('C3', 'Jl. Sample No. 2');
    $sheet->setCellValue('D3', 'Pare');

    // Style headers
    $sheet->getStyle('A1:D1')->getFont()->setBold(true);

    // Auto-size columns
    foreach (range('A', 'D') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($path);
  }
}
