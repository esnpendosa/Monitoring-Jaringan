<?php

namespace App\Http\Controllers;

use App\Services\KnnReportService;
use Illuminate\Http\Request;

class KnnReportController extends Controller
{
    protected $reportService;

    public function __construct(KnnReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $perbandinganK3 = $this->reportService->getPerbandinganK3();
        $evaluasiK = $this->reportService->getEvaluasiAkurasiSemuaK();
        $classificationK3 = $this->reportService->getClassificationReportK3();

        return view('content.knn.report', compact('perbandinganK3', 'evaluasiK', 'classificationK3'));
    }
}
