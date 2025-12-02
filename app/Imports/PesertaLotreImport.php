<?php

namespace App\Imports;

use App\Models\PesertaLotre;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class PesertaLotreImport implements ToCollection, WithHeadingRow, WithChunkReading
{
  private int $imported = 0;
  private int $skipped = 0;
  private int $duplicates = 0;
  private array $errors = [];

  /**
   * Process the collection of rows from the Excel file
   */
  public function collection(Collection $rows)
  {
    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // heading row is #1 when WithHeadingRow

      try {
        // Extract and normalize data from row
        $nama = $this->extractNama($row);
        $nopol = $this->extractNopol($row);
        $alamat = $this->extractAlamat($row);
        $kecamatan = $this->extractKecamatan($row);

        // Validate required fields
        if (empty($nama) && empty($nopol)) {
          $this->skipped++;
          $this->errors[] = "Row {$rowNumber}: nama dan nopol kosong, minimal salah satu harus diisi.";
          continue;
        }

        // Check for duplicates based on nopol (if provided)
        if (!empty($nopol)) {
          $existing = PesertaLotre::where('nopol', $nopol)->first();
          if ($existing) {
            $this->duplicates++;
            $this->skipped++;
            $this->errors[] = "Row {$rowNumber}: nopol '{$nopol}' sudah ada di database, dilewati.";
            continue;
          }
        }

        // Create the participant with default values for apakah_menang and urutan_menang
        PesertaLotre::create([
          'nama' => $nama,
          'nopol' => $nopol,
          'alamat' => $alamat,
          'kecamatan' => $kecamatan,
          'apakah_menang' => false,
          'urutan_menang' => null,
        ]);

        $this->imported++;
      } catch (\Throwable $e) {
        $this->skipped++;
        $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
        Log::warning('PesertaLotre import skipped row: ' . $e->getMessage());
        continue;
      }
    }
  }

  /**
   * Extract nama from row - check multiple possible column names
   */
  private function extractNama($row): ?string
  {
    $possibleKeys = ['nama', 'name', 'nama_peserta', 'peserta'];

    foreach ($possibleKeys as $key) {
      if (isset($row[$key]) && !empty(trim((string)$row[$key]))) {
        return trim((string)$row[$key]);
      }
    }

    return null;
  }

  /**
   * Extract nopol from row - check multiple possible column names
   */
  private function extractNopol($row): ?string
  {
    $possibleKeys = ['nopol', 'no_pol', 'nomor_polisi', 'plat', 'plat_nomor'];

    foreach ($possibleKeys as $key) {
      if (isset($row[$key]) && !empty(trim((string)$row[$key]))) {
        // Normalize nopol: uppercase and remove extra spaces
        $nopol = strtoupper(trim((string)$row[$key]));
        $nopol = preg_replace('/\s+/', ' ', $nopol);
        return $nopol;
      }
    }

    return null;
  }

  /**
   * Extract alamat from row - check multiple possible column names
   */
  private function extractAlamat($row): ?string
  {
    $possibleKeys = ['alamat', 'address', 'alamat_peserta'];

    foreach ($possibleKeys as $key) {
      if (isset($row[$key]) && !empty(trim((string)$row[$key]))) {
        return trim((string)$row[$key]);
      }
    }

    return null;
  }

  /**
   * Extract kecamatan from row - check multiple possible column names
   */
  private function extractKecamatan($row): ?string
  {
    $possibleKeys = ['kecamatan', 'kec', 'district', 'wilayah'];

    foreach ($possibleKeys as $key) {
      if (isset($row[$key]) && !empty(trim((string)$row[$key]))) {
        return trim((string)$row[$key]);
      }
    }

    return null;
  }

  /**
   * Define chunk size for memory efficiency
   */
  public function chunkSize(): int
  {
    return 500;
  }

  /**
   * Get count of successfully imported records
   */
  public function getImportedCount(): int
  {
    return $this->imported;
  }

  /**
   * Get count of skipped records
   */
  public function getSkippedCount(): int
  {
    return $this->skipped;
  }

  /**
   * Get count of duplicate records
   */
  public function getDuplicatesCount(): int
  {
    return $this->duplicates;
  }

  /**
   * Get array of error messages
   */
  public function getErrors(): array
  {
    return $this->errors;
  }
}
