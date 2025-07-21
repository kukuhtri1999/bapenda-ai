<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureWajibPajakData
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
   * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
   */
  public function handle(Request $request, Closure $next)
  {
    // Check if user has provided wajib pajak data in session
    if (!session()->has('wajib_pajak_data')) {
      // If request is API/AJAX, return JSON response
      if ($request->expectsJson()) {
        return response()->json([
          'success' => false,
          'message' => 'Data wajib pajak diperlukan sebelum menggunakan layanan ini',
          'redirect' => route('wajib-pajak.form')
        ], 403);
      }

      // For web requests, redirect to form
      return redirect()->route('wajib-pajak.form')
        ->with('error', 'Silakan isi data wajib pajak terlebih dahulu sebelum menggunakan layanan chat AI.');
    }

    return $next($request);
  }
}
