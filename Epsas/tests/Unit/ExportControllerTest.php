<?php

namespace Tests\Unit;

use App\Http\Controllers\ExportController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    public function test_pdf_export_accepts_at_most_500_rows(): void
    {
        $this->assertSame(500, $this->pdfLimit(500));
    }

    public function test_pdf_export_rejects_larger_requests(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Los PDF se limitan a 500 filas. Usa CSV para exportaciones mayores.');

        $this->pdfLimit(501);
    }

    private function pdfLimit(int $limit): int
    {
        $method = new \ReflectionMethod(ExportController::class, 'pdfExportLimit');

        return $method->invoke(new ExportController, Request::create('/export', 'GET', ['limit' => $limit]));
    }
}
