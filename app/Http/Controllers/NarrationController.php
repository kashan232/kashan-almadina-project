<?php


namespace App\Http\Controllers;

use App\Models\Narration;
use Illuminate\Http\Request;

class NarrationController extends Controller
{
    public function index(Request $request)
    {
        $query = Narration::query();

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $narrations = $query->latest()->get();
        return view('admin_panel.accounts.narration', compact('narrations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'expense_head' => 'required|string|max:255',
            'narration' => 'required|string',
        ]);

        if ($request->id) {
            // Update
            $narration = Narration::findOrFail($request->id);
            $narration->update([
                'expense_head' => $request->expense_head,
                'narration' => $request->narration,
            ]);
            return redirect()->back()->with('success', 'Narration updated successfully.');
        } else {
            // Create
            Narration::create([
                'expense_head' => $request->expense_head,
                'narration' => $request->narration,
            ]);
            return redirect()->back()->with('success', 'Narration added successfully.');
        }
    }

    public function destroy($id)
    {
        Narration::findOrFail($id)->delete();
        return redirect()->route('coa.narration')->with('success', 'Narration deleted successfully.');
    }

    // API: Get narrations for Receipt Vouchers (JSON)
    public function getForReceipts()
    {
        $narrations = Narration::where('expense_head', 'Receipts Voucher')
            ->select('id', 'narration as narration_text', 'expense_head')
            ->get();
        
        return response()->json($narrations);
    }
}
