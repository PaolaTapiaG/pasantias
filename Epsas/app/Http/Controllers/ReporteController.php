<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    public function index(Request $request)
    {
        return $this->reports->index($request);
    }

    public function warmIndexCache(): void
    {
        $this->reports->warmIndexCache();
    }
}
