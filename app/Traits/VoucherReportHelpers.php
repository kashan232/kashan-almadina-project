<?php

namespace App\Traits;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\Narration;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Vendor;
use Illuminate\Http\Request;

trait VoucherReportHelpers
{
    protected function shouldApplyFilter(array $selected, int $total): bool
    {
        return !empty($selected) && ($total === 0 || count($selected) < $total);
    }

    protected function applyDateFilter($query, ?string $from, ?string $to, string $column): void
    {
        if (!empty($from)) {
            $query->whereDate($column, '>=', $from);
        }
        if (!empty($to)) {
            $query->whereDate($column, '<=', $to);
        }
    }

    protected function resolvePartyName(?string $type, $partyId): string
    {
        if (is_numeric($type)) {
            $account = Account::find($partyId);

            return strtoupper($account->title ?? 'N/A');
        }

        if ($type === 'vendor') {
            $vendor = Vendor::find($partyId);

            return strtoupper($vendor->name ?? 'N/A');
        }

        if (in_array($type, ['customer', 'walkin', 'subcustomer'], true)) {
            $customer = Customer::find($partyId);

            return strtoupper($customer->customer_name ?? 'N/A');
        }

        return 'N/A';
    }

    protected function resolveNarration($narrId): string
    {
        if (empty($narrId)) {
            return 'N/A';
        }
        if (is_numeric($narrId)) {
            return Narration::find($narrId)?->narration ?? 'N/A';
        }

        return (string) $narrId;
    }

    protected function loadVoucherReportFilters(): array
    {
        $userGroups = UserGroup::orderBy('group_name')->get();
        $users = User::with('userGroups')->orderBy('name')->get();
        $accountHeads = AccountHead::where('status', 1)->orderBy('name')->get();
        $accounts = Account::with('head')->orderBy('title')->get();
        $customers = Customer::orderBy('customer_name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $shopGroupIds = $userGroups->where('allow_shop', 1)->pluck('id')->implode(',');

        return compact('userGroups', 'users', 'accountHeads', 'accounts', 'customers', 'vendors', 'shopGroupIds');
    }

    protected function applyCommonVoucherFilters($query, Request $request, string $voucherIdColumn): void
    {
        $user_groups = $request->user_group ?? [];
        $officers = $request->sales_officer ?? [];
        $voucherId = $request->voucher_id;

        $totalGroups = UserGroup::count();
        $totalUsers = User::count();

        if (!empty($voucherId)) {
            $query->where(function ($sub) use ($voucherId, $voucherIdColumn) {
                $sub->where($voucherIdColumn, 'like', "%{$voucherId}%")
                    ->orWhere('id', 'like', "%{$voucherId}%")
                    ->orWhere($voucherIdColumn, 'like', '%' . ltrim($voucherId, '0') . '%');
            });
        }

        if ($this->shouldApplyFilter($officers, $totalUsers)) {
            $query->whereIn('created_by', $officers);
        }

        if ($this->shouldApplyFilter($user_groups, $totalGroups)) {
            $query->where(function ($sub) use ($user_groups) {
                foreach ($user_groups as $gid) {
                    $sub->orWhereJsonContains('user_group_ids', (string) $gid)
                        ->orWhereJsonContains('user_group_ids', (int) $gid);
                }
            });
        }
    }

    protected function passesHeadAccountFilter(
        array $mainHeads,
        array $subHeads,
        array $selectedAccounts,
        $headId,
        $accountId,
        int $totalAccountHeads,
        int $totalAccounts
    ): bool {
        $activeHeadFilter = !empty($subHeads) ? $subHeads : $mainHeads;
        if ($this->shouldApplyFilter($activeHeadFilter, $totalAccountHeads) && !in_array((string) $headId, array_map('strval', $activeHeadFilter), true)) {
            return false;
        }
        if ($this->shouldApplyFilter($selectedAccounts, $totalAccounts) && !in_array((string) $accountId, array_map('strval', $selectedAccounts), true)) {
            return false;
        }

        return true;
    }

    protected function applyPartyFilter($query, Request $request, string $typeColumn = 'type', string $partyIdColumn = 'party_id'): void
    {
        $parties = $request->party ?? [];
        $totalParties = Customer::count() + Vendor::count();

        if (!$this->shouldApplyFilter($parties, $totalParties)) {
            return;
        }

        $query->where(function ($sub) use ($parties, $typeColumn, $partyIdColumn) {
            foreach ($parties as $party) {
                if (!str_contains($party, ':')) {
                    continue;
                }
                [$type, $id] = explode(':', $party, 2);
                $sub->orWhere(function ($sq) use ($type, $id, $typeColumn, $partyIdColumn) {
                    $sq->where($typeColumn, $type)->where($partyIdColumn, $id);
                });
            }
        });
    }

    protected function applyPostedStatusFilter($query): void
    {
        $query->whereIn('status', ['posted', 'Posted']);
    }

    /** @return array<int, mixed> */
    protected function decodeJsonRows(?string $json, mixed $fallback = []): array
    {
        $decoded = json_decode($json ?? '', true);
        if (is_array($decoded)) {
            return $decoded;
        }
        if ($decoded === null || $decoded === '') {
            return is_array($fallback) ? $fallback : [];
        }

        return [$decoded];
    }
}
