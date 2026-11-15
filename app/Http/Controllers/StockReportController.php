<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\UserGroup;
use App\Models\Warehouse;
use App\Services\StockReportBuilder;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class StockReportController extends Controller
{
    public function index()
    {
        return $this->filterView('admin_panel.reports.stock.index', [
            'pageTitle' => 'Stock Report Filters',
            'previewRoute' => route('reports.stock.preview'),
            'fixedReportType' => 'summary',
        ]);
    }

    public function ledgerIndex()
    {
        return $this->filterView('admin_panel.reports.stock.index', [
            'pageTitle' => 'Item Stock Ledger Filters',
            'previewRoute' => route('reports.stock-ledger.preview'),
            'fixedReportType' => 'ledger',
        ]);
    }

    public function preview(Request $request, StockReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'report_type' => 'required|in:summary,merged,retail,hold',
        ]);

        if ($request->report_type === 'merged') {
            $data = $builder->buildMerged($request);

            return view('admin_panel.reports.stock.preview', $data);
        }

        if ($request->report_type === 'retail') {
            $data = $builder->buildRetail($request);

            return view('admin_panel.reports.stock.preview_retail', $data);
        }

        if ($request->report_type === 'hold') {
            $data = $builder->buildHold($request);

            return view('admin_panel.reports.stock.preview_hold', $data);
        }

        $data = $builder->build($request);

        return view('admin_panel.reports.stock.preview', $data);
    }

    public function ledgerPreview(Request $request, StockReportBuilder $builder)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'item' => 'required|array|min:1',
            'item.*' => 'integer|exists:products,id',
        ], [
            'item.required' => 'Please select at least one Item for the Item Stock Ledger.',
            'item.min' => 'Please select at least one Item for the Item Stock Ledger.',
        ]);

        $data = $builder->buildLedger($request);

        return view('admin_panel.reports.stock.preview_ledger', $data);
    }

    public function exportPdf(Request $request, StockReportBuilder $builder)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'report_type' => 'required|in:summary,merged,retail,hold',
        ]);

        $reportType = $request->report_type;
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        if ($reportType === 'merged') {
            $data = $builder->buildMerged($request);
            $view = 'admin_panel.reports.stock.pdf_stock';
            $orientation = 'landscape';
            $filename = "Stock_Report_Merged_{$fromDate}_to_{$toDate}.pdf";
        } elseif ($reportType === 'retail') {
            $data = $builder->buildRetail($request);
            $view = 'admin_panel.reports.stock.pdf_retail';
            $orientation = 'portrait';
            $filename = "Stock_Report_With_Retail_{$fromDate}_to_{$toDate}.pdf";
        } elseif ($reportType === 'hold') {
            $data = $builder->buildHold($request);
            $view = 'admin_panel.reports.stock.pdf_hold';
            $orientation = 'portrait';
            $filename = "Stock_Report_Hold_Qty_{$fromDate}_to_{$toDate}.pdf";
        } else {
            $data = $builder->build($request);
            $view = 'admin_panel.reports.stock.pdf_stock';
            $orientation = 'landscape';
            $filename = "Stock_Report_Without_Values_{$fromDate}_to_{$toDate}.pdf";
        }

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', $orientation)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');
        $canvas->page_text(
            $canvas->get_width() - 65,
            $canvas->get_height() - 16,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            7.5,
            [0.2, 0.2, 0.2]
        );

        return $pdf->download($filename);
    }

    public function ledgerExportPdf(Request $request, StockReportBuilder $builder)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'item' => 'required|array|min:1',
            'item.*' => 'integer|exists:products,id',
        ], [
            'item.required' => 'Please select at least one Item for the Item Stock Ledger.',
            'item.min' => 'Please select at least one Item for the Item Stock Ledger.',
        ]);

        $data = $builder->buildLedger($request);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;
        $filename = "Item_Stock_Ledger_{$fromDate}_to_{$toDate}.pdf";

        $pdf = Pdf::loadView('admin_panel.reports.stock.pdf_ledger', $data)
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->get_font('Helvetica', 'normal');
        $canvas->page_text(
            $canvas->get_width() - 65,
            $canvas->get_height() - 16,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            $font,
            7.5,
            [0.2, 0.2, 0.2]
        );

        return $pdf->download($filename);
    }

    private function filterView(string $view, array $extra = [])
    {
        $user = auth()->user();
        $isAdmin = !$user || $user->roles->pluck('name')->contains('Admin') || $user->id == 1;

        $userGroups = UserGroup::orderBy('group_name')
            ->when(!$isAdmin, function ($q) use ($user) {
                $groupIds = $user ? $user->userGroups()->pluck('user_groups.id')->toArray() : [];
                $q->whereIn('id', $groupIds);
            })
            ->get();
        $warehouses = Warehouse::withoutGlobalScopes()->orderBy('warehouse_name')->get();
        $categories = Category::orderBy('id')->get();
        $subcategories = Subcategory::orderBy('id')->get();
        $brands = Brand::orderBy('id')->get();
        $products = Product::orderByRaw('COALESCE(brand_id, 999999) asc')
            ->orderByRaw('COALESCE(sub_category_id, 999999) asc')
            ->orderBy('id', 'asc')
            ->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return view($view, array_merge(compact(
            'userGroups',
            'warehouses',
            'categories',
            'subcategories',
            'brands',
            'products',
            'shopGroupIds'
        ), $extra));
    }
}
