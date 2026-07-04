<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\IncomeVoucher;
use App\Models\Vendor;
use App\Traits\VoucherReportHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class IncomeVoucherReportController extends Controller
{
    use VoucherReportHelpers;

    public function index()
    {
        return view('admin_panel.reports.income_voucher.index', $this->loadVoucherReportFilters());
    }

    public function preview(Request $request)
    {
        $report_type = $request->input('report_type', 'destination_account');
        $entry_from = $request->entry_from;
        $entry_to = $request->entry_to;
        $grouped = $this->fetchReportLines($request)->groupBy('group_key');

        return view('admin_panel.reports.income_voucher.preview', compact(
            'grouped', 'report_type', 'entry_from', 'entry_to'
        ));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $parties = $request->party ?? [];
        $mainHeads = $request->main_head ?? [];
        $subHeads = $request->sub_head ?? $request->account_head ?? [];
        $accounts = $request->account ?? [];
        $report_type = $request->input('report_type', 'destination_account');
        $totalParties = Customer::count() + Vendor::count();
        $totalAccountHeads = AccountHead::count();
        $totalAccounts = Account::count();

        $query = IncomeVoucher::query()->withoutGlobalScopes();
        $this->applyPostedStatusFilter($query);
        $this->applyDateFilter($query, $request->entry_from, $request->entry_to, 'entry_date');
        $this->applyCommonVoucherFilters($query, $request, 'ivid');

        $lines = collect();
        foreach ($query->orderBy('entry_date')->orderBy('id')->get() as $voucher) {
            $lines = $lines->merge($this->expandLines($voucher, $report_type, $parties, $totalParties, $mainHeads, $subHeads, $accounts, $totalAccountHeads, $totalAccounts));
        }

        return $lines->sortBy(fn ($line) => sprintf(
            '%s-%s-%s',
            $line->sort_group ?? '',
            $line->voucher_date ?? '',
            str_pad((string) ($line->voucher_no ?? ''), 10, '0', STR_PAD_LEFT)
        ))->values();
    }

    private function expandLines(
        IncomeVoucher $voucher,
        string $reportType,
        array $parties,
        int $totalParties,
        array $mainHeads,
        array $subHeads,
        array $selectedAccounts,
        int $totalAccountHeads,
        int $totalAccounts
    ): Collection {
        $destHeadName = AccountHead::find($voucher->account_head)?->name ?? 'N/A';
        $destAccount = Account::find($voucher->account_id);
        $destLabel = strtoupper($destAccount->title ?? $destHeadName);

        $narrationIds = json_decode($voucher->narration_id, true) ?? [];
        $partyTypes = json_decode($voucher->party_type, true) ?? [];
        $partyIds = json_decode($voucher->party_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];
        $lines = collect();

        foreach ($narrationIds as $index => $narrId) {
            $pType = $partyTypes[$index] ?? null;
            $pId = $partyIds[$index] ?? null;
            $partyName = $this->resolvePartyName($pType, $pId);
            $amount = (float) ($amounts[$index] ?? 0);

            if ($this->shouldApplyFilter($parties, $totalParties)) {
                $match = false;
                foreach ($parties as $party) {
                    if (!str_contains($party, ':')) {
                        continue;
                    }
                    [$type, $id] = explode(':', $party, 2);
                    if ((string) $pType === (string) $type && (string) $pId === (string) $id) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    continue;
                }
            }

            if (!$this->passesHeadAccountFilter($mainHeads, $subHeads, $selectedAccounts, $voucher->account_head, $voucher->account_id, $totalAccountHeads, $totalAccounts)) {
                continue;
            }

            if ($amount == 0.0 && empty($narrId) && empty($pId)) {
                continue;
            }

            $groupKey = $reportType === 'source_party'
                ? 'party_' . $pType . '_' . $pId
                : 'head_' . ($voucher->account_head ?: '0') . '_' . ($voucher->account_id ?: '0');
            $groupLabel = $reportType === 'source_party' ? $partyName : $destLabel;

            $lines->push((object) [
                'group_key' => $groupKey,
                'group_label' => $groupLabel,
                'sort_group' => $groupLabel,
                'voucher_no' => $voucher->ivid,
                'voucher_date' => $voucher->entry_date,
                'reference_no' => $references[$index] ?? '',
                'narration' => $this->resolveNarration($narrId),
                'party_name' => $partyName,
                'account_name' => $destLabel,
                'amount' => $amount,
            ]);
        }

        return $lines;
    }
}
