<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PesertaLotreImport;
use App\Models\PesertaLotre;
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

    return Inertia::render('Admin/ImportLotreData/Index', [
      'stats' => [
        'total_participants' => $totalParticipants,
        'total_winners' => $totalWinners,
      ],
    ]);
  }

  /**
   * Preview the first 10 rows of the uploaded file without importing
   */
  public function preview(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
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
      $file = $request->file('file');
      $spreadsheet = IOFactory::load($file->getPathname());
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

        // Skip completely empty rows
        if (empty($nama) && empty($nopol) && empty($alamat)) {
          continue;
        }

        $previewData[] = [
          'row' => $i + 1, // 1-indexed, +1 for header
          'nama' => $nama,
          'nopol' => $nopol,
          'alamat' => $alamat,
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

    // Create a simple CSV template (will work for both xlsx and csv imports)
    $header = "nama,nopol,alamat\n";
    $sample1 = "John Doe,AB 1234 CD,Jl. Contoh No. 1\n";
    $sample2 = "Jane Smith,EF 5678 GH,Jl. Sample No. 2\n";

    // For xlsx, we'll use PhpSpreadsheet through Laravel Excel
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'nama');
    $sheet->setCellValue('B1', 'nopol');
    $sheet->setCellValue('C1', 'alamat');

    // Set sample data
    $sheet->setCellValue('A2', 'John Doe');
    $sheet->setCellValue('B2', 'AB 1234 CD');
    $sheet->setCellValue('C2', 'Jl. Contoh No. 1');

    $sheet->setCellValue('A3', 'Jane Smith');
    $sheet->setCellValue('B3', 'EF 5678 GH');
    $sheet->setCellValue('C3', 'Jl. Sample No. 2');

    // Style headers
    $sheet->getStyle('A1:C1')->getFont()->setBold(true);

    // Auto-size columns
    foreach (range('A', 'C') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($path);
  }
}
