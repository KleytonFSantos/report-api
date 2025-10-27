<?php

use App\Jobs\ProcessCsvReport;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/reports', function (Request $request) {
    return Report::query()->latest()->get();
});

Route::post('/reports', function (Request $request) {
    $request->validate([
        'file' => 'required|file|mimes:csv,txt',
    ]);

    $path = $request->file('file')->store('inputs');
    $originalName = $request->file('file')->getClientOriginalName();

    $report = Report::query()->create([
        'status' => 'pendente',
        'original_filename' => $originalName,
        'input_path' => $path,
    ]);

    ProcessCsvReport::dispatch($report->id);

    return response()->json($report, 201);
});

Route::get('/reports/{report}/download', function (Request $request, Report $report) {
    if (! $report) {
        return response()->json(['message' => 'Relatório não encontrado'], 404);
    }

    if ($report->status !== 'concluido' || ! $report->output_path) {
        return response()->json(['message' => 'Relatório não está pronto'], 404);
    }

    return Storage::download($report->output_path, $report->original_filename.'.pdf');
});
