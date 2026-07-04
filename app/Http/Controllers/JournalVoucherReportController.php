<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\JournalVoucher;
use App\Models\Vendor;
use App\Traits\VoucherReportHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class JournalVoucherReportController extends Controller
{
    use VoucherReportHelpers;

    public function index()
    {
        return view('admin_panel.reports.journal_voucher.index', $this->loadVoucherReportFilters());
    }

    public function preview(Request $request)
    {
        $entry_from = $request->entry_from;
        $entry_to = $request->entry_to;
        $grouped = $this->fetchReportLines($request)->groupBy('group_key');

        return view('admin_panel.reports.journal_voucher.preview', compact(
            'grouped', 'entry_from', 'entry_to'
        ));
    }

    private function fetchReportLines(Request $request): Collection
    {
        $parties = $request->party ?? [];
        $totalParties = Customer::count() + Vendor::count();

        $query = JournalVoucher::query()->withoutGlobalScopes();
        $this->applyPostedStatusFilter($query);
        $this->applyDateFilter($query, $request->entry_from, $request->entry_to, 'entry_date');
        $this->applyCommonVoucherFilters($query, $request, 'jvid');

        $lines = collect();
        foreach ($query->orderBy('entry_date')->orderBy('id')->get() as $voucher) {
            $lines = $lines->merge($this->expandLines($voucher, $parties, $totalParties));
        }

        return $lines->sortBy(fn ($line) => sprintf(
            '%s-%s-%s',
            $line->sort_group ?? '',
            $line->voucher_date ?? '',
            str_pad((string) ($line->voucher_no ?? ''), 10, '0', STR_PAD_LEFT)
        ))->values();
    }

    private function expandLines(JournalVoucher $voucher, array $parties, int $totalParties): Collection
    {
        $narrationIds = json_decode($voucher->narration_id, true) ?? [];
        $partyTypes = json_decode($voucher->party_type, true) ?? [];
        $partyIds = json_decode($voucher->party_id, true) ?? [];
        $debits = json_decode($voucher->debit, true) ?? [];
        $credits = json_decode($voucher->credit, true) ?? [];
        $lines = collect();

        foreach ($narrationIds as $index => $narrId) {
            $pType = $partyTypes[$index] ?? null;
            $pId = $partyIds[$index] ?? null;
            $partyName = $this->resolvePartyName($pType, $pId);
            $debit = (float) ($debits[$index] ?? 0);
            $credit = (float) ($credits[$index] ?? 0);

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

            if ($debit == 0.0 && $credit == 0.0 && empty($narrId) && empty($pId)) {
                continue;
            }

            $groupKey = 'party_' . $pType . '_' . $pId;
            $lines->push((object) [
                'group_key' => $groupKey,
                'group_label' => $partyName,
                'sort_group' => $partyName,
                'voucher_no' => $voucher->jvid,
                'voucher_date' => $voucher->entry_date,
                'reference_no' => $voucher->reference_no ?? '',
                'narration' => $this->resolveNarration($narrId),
                'party_name' => $partyName,
                'debit' => $debit,
                'credit' => $credit,
            ]);
        }

        return $lines;
    }
}
