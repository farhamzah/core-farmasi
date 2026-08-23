<?php

namespace App\Http\Controllers\Admin;

use App\Exports\MasterDataReportExport;
use App\Http\Controllers\Controller;
use App\Services\MasterDataReportService;
use App\Services\SimplePdfReportBuilder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MasterDataReportController extends Controller
{
    public function __construct(
        protected MasterDataReportService $reports,
        protected SimplePdfReportBuilder $pdf,
    ) {}

    public function show(Request $request, string $type)
    {
        $this->authorizeAdmin();

        $report = $this->reports->build($type, $request->query());

        return view('admin.master-data-report', [
            'report' => $report,
            'type' => $type,
        ]);
    }

    public function excel(Request $request, string $type)
    {
        $this->authorizeAdmin();

        $report = $this->reports->build($type, $request->query());
        $filename = str($report['config']['route_key'])->replace('-', '_')->append('_report.xlsx')->toString();

        return Excel::download(
            new MasterDataReportExport($report['columns'], $report['rows']),
            $filename,
        );
    }

    public function pdf(Request $request, string $type)
    {
        $this->authorizeAdmin();

        $report = $this->reports->build($type, $request->query());
        $filename = str($report['config']['route_key'])->replace('-', '_')->append('_report.pdf')->toString();

        $pdf = $this->pdf->build(
            $report['config']['label'],
            $report['columns'],
            $report['rows'],
            $report['active_filters'],
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function authorizeAdmin(): void
    {
        $user = auth()->user();

        abort_unless($user?->roles()->whereIn('name', ['super-admin', 'admin-core'])->where('active', true)->exists(), 403);
    }
}
