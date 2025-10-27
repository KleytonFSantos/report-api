<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Log\Logger;
use Tests\TestCase;
use App\Models\Report;
use App\Jobs\ProcessCsvReport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Kleytondev\CsvReportGenerator\ReportGenerator;
use \Mockery;

class ProcessCsvReportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Testa o "caminho feliz" (happy path) onde tudo funciona.
     * @test
     */
    public function it_processes_report_successfully()
    {
        Storage::fake('local');

        $fakeCsvPath = 'private/uploads/test.csv';
        Storage::put($fakeCsvPath, 'id,produto,quantidade\n1,teste,10');

        $report = Report::factory()->create([
            'status' => 'pendente',
            'original_filename' => 'test.csv',
            'input_path' => $fakeCsvPath,
        ]);

        $generatorMock = $this->mock(ReportGenerator::class);

        $generatorMock->shouldReceive('process')
            ->once()
            ->withArgs(function ($input, $output) use ($report) {
                $this->assertEquals(Storage::path($report->input_path), $input);

                $this->assertStringContainsString(Storage::path('private/reports/'), $output);
                $this->assertStringContainsString('_' . $report->id . '.pdf', $output);
                return true;
            });

        $job = new ProcessCsvReport($report->id);
        $job->handle($generatorMock, Log::driver());

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'concluido',
            'error_message' => null
        ]);

        $report->refresh(); // Pega os dados atualizados do DB
        $this->assertNotNull($report->output_path);
        $this->assertStringContainsString('private/reports/', $report->output_path);
    }

    /**
     * Testa o "caminho triste" (sad path) onde o pacote falha.
     * @test
     */
    public function it_handles_processing_failure()
    {
        Storage::fake('local');
        $fakeCsvPath = 'private/uploads/fail.csv';
        Storage::put($fakeCsvPath, 'data');

        $report = Report::factory()->create([
            'status' => 'pendente',
            'original_filename' => 'fail.csv',
            'input_path' => $fakeCsvPath,
        ]);

        $generatorMock = $this->mock(ReportGenerator::class);
        $generatorMock->shouldReceive('process')
            ->once()
            ->andThrow(new \Exception('Falha ao gerar PDF'));

        try {
            $job = new ProcessCsvReport($report->id);
            $job->handle($generatorMock, Log::driver());
        } catch (\Exception $e) {
            $this->assertEquals('Falha ao gerar PDF', $e->getMessage());
        }

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'falhou',
            'error_message' => 'Falha ao gerar PDF'
        ]);
    }

    /**
     * Testa o que acontece se o ID do relatório não for encontrado.
     * @test
     */
    public function it_handles_non_existent_report()
    {
        $generatorMock = $this->mock(ReportGenerator::class);

        $loggerMock = $this->mock(Logger::class);
        $loggerMock->shouldReceive('error')
            ->once()
            ->with('Job falhou: Relatório com ID 999 não encontrado.');

        $job = new ProcessCsvReport(999);
        $job->handle($generatorMock, $loggerMock);

        $this->assertDatabaseCount('reports', 0);
    }
}
