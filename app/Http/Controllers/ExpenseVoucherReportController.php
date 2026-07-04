<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\ExpenseVoucher;
use App\Traits\VoucherReportHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExpenseVoucherReportController extends Controller
{
    use VoucherReportHelpers;

    public function index()
    {
        return view('admin_panel.reports.expense_voucher.index', $this->loadVoucherReportFilters());
    }

    public function preview(Request $request)
    {
        $report_type = $request->input('report_type', 'source_party');
        $entry_from = $request->entry_from;
        $entry_to = $request->entry_to;
        $grouped = $this->fetchReportLines($request)->groupBy('group_key');

        return view('admin_panel.reports.expense_voucher.preview', compact(
            'grouped', 'report_type', 'entry_from', 'entry_to'
        ));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $mainHeads = $request->main_head ?? [];
        $subHeads = $request->sub_head ?? $request->account_head ?? [];
        $accounts = $request->account ?? [];
        $report_type = $request->input('report_type', 'source_party');
        $totalAccountHeads = AccountHead::count();
        $totalAccounts = Account::count();

        $query = ExpenseVoucher::query()->withoutGlobalScopes();
        $this->applyPostedStatusFilter($query);
        $this->applyDateFilter($query, $request->entry_from, $request->entry_to, 'entry_date');
        $this->applyCommonVoucherFilters($query, $request, 'evid');
        $this->applyPartyFilter($query, $request);

        $lines = collect();
        foreach ($query->orderBy('entry_date')->orderBy('id')->get() as $voucher) {
            $lines = $lines->merge($this->expandLines($voucher, $report_type, $mainHeads, $subHeads, $accounts, $totalAccountHeads, $totalAccounts));
        }

        return $lines->sortBy(fn ($line) => sprintf(
            '%s-%s-%s',
            $line->sort_group ?? '',
            $line->voucher_date ?? '',
            str_pad((string) ($line->voucher_no ?? ''), 10, '0', STR_PAD_LEFT)
        ))->values();
    }

    private function expandLines(
        ExpenseVoucher $voucher,
        string $reportType,
        array $mainHeads,
        array $subHeads,
        array $selectedAccounts,
        int $totalAccountHeads,
        int $totalAccounts
    ): Collection {
        $partyName = $this->resolvePartyName($voucher->type, $voucher->party_id);
        $narrationIds = $this->decodeJsonRows($voucher->narration_id);
        $rowHeads = $this->decodeJsonRows($voucher->row_account_head);
        $rowAccounts = $this->decodeJsonRows($voucher->row_account_id);
        $amounts = $this->decodeJsonRows($voucher->amount);
        if (empty($narrationIds) && !empty($amounts)) {
            $narrationIds = array_fill(0, count($amounts), null);
        }
        $lines = collect();

        foreach ($narrationIds as $index => $narrId) {
            $headId = $rowHeads[$index] ?? null;
            $headName = $headId ? (AccountHead::find($headId)?->name ?? 'N/A') : 'N/A';
            $accountId = $rowAccounts[$index] ?? null;

            if (!$this->passesHeadAccountFilter($mainHeads, $subHeads, $selectedAccounts, $headId, $accountId, $totalAccountHeads, $totalAccounts)) {
                continue;
            }

            $amount = (float) ($amounts[$index] ?? 0);
            if ($amount == 0.0 && empty($narrId) && empty($accountId)) {
                continue;
            }

            $account = !empty($accountId) ? Account::find($accountId) : null;
            $groupKey = $reportType === 'sub_head' ? 'head_' . ($headId ?: '0') : 'party_' . $voucher->type . '_' . $voucher->party_id;
            $groupLabel = $reportType === 'sub_head' ? strtoupper($headName) : $partyName;

            $lines->push((object) [
                'group_key' => $groupKey,
                'group_label' => $groupLabel,
                'sort_group' => $groupLabel,
                'voucher_no' => $voucher->evid,
                'voucher_date' => $voucher->entry_date,
                'narration' => $this->resolveNarration($narrId),
                'sub_head_name' => $headName,
                'party_name' => $partyName,
                'bank_details' => $account->title ?? '-',
                'amount' => $amount,
            ]);
        }

        return $lines;
    }
}
