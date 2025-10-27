<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Kleytondev\CsvReportGenerator\ReportGenerator;
use Illuminate\Log\Logger;

class ProcessCsvReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $reportId;

    public function __construct(int $reportId)
    {
        $this->reportId = $reportId;
    }

    public function handle(ReportGenerator $reportGenerator, Logger $logger): void
    {
        $report = Report::find($this->reportId);

        if (!$report) {
            $logger->error("Job falhou: Relatório com ID {$this->reportId} não encontrado.");
            return;
        }

        $report->update(['status' => 'processando']);

        try {
            $directory = 'private/reports';
            $filename = uniqid() . '_' . $report->id . '.pdf'; // Agora $report->id não estará nulo
            $outputPath = $directory . '/' . $filename;

            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }

            if (!Storage::exists($directory)) {
                throw new \Exception("Falha ao criar diretório: " . Storage::path($directory));
            }

            $inputPath = Storage::path($report->input_path);
            $absoluteOutputPath = Storage::path($outputPath);

            $reportGenerator->process($inputPath, $absoluteOutputPath);

            $report->update([
                'status' => 'concluido',
                'output_path' => $outputPath
            ]);

        } catch (\Exception $e) {
            $report->update([
                'status' => 'falhou',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}

