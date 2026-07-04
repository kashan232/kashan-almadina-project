<?php

namespace App\Http\Controllers;

use App\Services\UnpostEntriesReportBuilder;
use Illuminate\Http\Request;

class UnpostEntriesReportController extends Controller
{
    public function index(Request $request, UnpostEntriesReportBuilder $builder)
    {
        $data = $builder->build($request);

        return view('admin_panel.reports.unpost_entries.preview', $data);
    }
}
