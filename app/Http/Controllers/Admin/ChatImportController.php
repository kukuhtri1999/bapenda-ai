<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ChatMessagesImport;

class ChatImportController extends Controller
{
  public function upload(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'file' => 'required|file|mimes:xlsx,xls,csv|max:20480', // up to 20MB
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'errors' => $validator->errors(),
      ], 422);
    }

    try {
      $import = new ChatMessagesImport();
      Excel::import($import, $request->file('file'));

      return response()->json([
        'success' => true,
        'imported' => $import->getImportedCount(),
        'skipped' => $import->getSkippedCount(),
        'errors' => $import->getErrors(),
      ]);
    } catch (\Throwable $e) {
      Log::error('Chat import failed: ' . $e->getMessage());
      return response()->json([
        'success' => false,
        'message' => 'Import failed. ' . $e->getMessage(),
      ], 500);
    }
  }
}
