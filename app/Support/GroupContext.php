<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GroupContext
{
    public const PARTY_FIELDS = [
        'party_type',
        'partyType',
        'vendor_type',
        'type',
        'party_id',
        'customer_id',
        'vendor_id',
        'purchasable_type',
        'purchasable_id',
        'warehouse_id',
        'claim_warehouse_id',
        'original_warehouse_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'replacement_from_warehouse_id',
        'sale_id',
        'purchase_id',
        'person',
    ];

    public static function partyFields(): array
    {
        return self::PARTY_FIELDS;
    }

    public static function isEmptyGroupIds(mixed $ids): bool
    {
        if ($ids === null || $ids === '' || $ids === []) {
            return true;
        }

        if (is_array($ids)) {
            return count(array_filter($ids, fn ($id) => $id !== null && $id !== '')) === 0;
        }

        return false;
    }

    public static function shouldSkipAutoResolve(Model $model): bool
    {
        return $model instanceof Customer
            || $model instanceof Vendor
            || $model instanceof Warehouse
            || $model instanceof Account;
    }

    public static function hasPartyFieldChanges(Model $model): bool
    {
        foreach (self::PARTY_FIELDS as $field) {
            if ($model->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    public static function fromAuthUser(): array
    {
        if (!Auth::check()) {
            return [];
        }

        return Auth::user()
            ->userGroups()
            ->pluck('user_groups.id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function resolveForModel(Model $model): array
    {
        if (self::shouldSkipAutoResolve($model)) {
            return self::fromAuthUser();
        }

        $groups = [];

        if (!empty($model->purchasable_type) && !empty($model->purchasable_id)) {
            $groups = array_merge($groups, self::fromMorph($model->purchasable_type, (int) $model->purchasable_id));
        }

        $partyType = $model->party_type ?? $model->partyType ?? $model->vendor_type ?? null;
        $partyId = $model->party_id ?? null;

        if (!$partyType && !empty($model->party_id) && isset($model->type)) {
            $rawType = strtolower(trim((string) $model->type));
            if ($rawType === '1') {
                $partyType = 'customer';
                $partyId = (int) $model->party_id;
            } elseif (!is_numeric($model->type)) {
                $partyType = $rawType;
                $partyId = (int) $model->party_id;
            }
        }

        if (!$partyId && !empty($model->customer_id)) {
            $partyId = (int) $model->customer_id;
            if (!$partyType) {
                $partyType = $model->party_type ?? $model->partyType ?? 'customer';
            }
        }

        if (!$partyId && !empty($model->vendor_id)) {
            $partyId = (int) $model->vendor_id;
            $partyType = $partyType ?? 'vendor';
        }

        if (!$partyId && !empty($model->person)) {
            $partyId = (int) $model->person;
            if (!$partyType && isset($model->type)) {
                $partyType = (string) $model->type;
            }
            if (!$partyType) {
                $partyType = 'customer';
            }
        }

        if ($partyId && $partyType) {
            $groups = array_merge($groups, self::fromPartyType((string) $partyType, (int) $partyId));
        }

        foreach ([
            'warehouse_id',
            'claim_warehouse_id',
            'original_warehouse_id',
            'from_warehouse_id',
            'to_warehouse_id',
            'replacement_from_warehouse_id',
        ] as $field) {
            $warehouseId = $model->{$field} ?? null;
            if ($warehouseId !== null && $warehouseId !== '' && (int) $warehouseId > 0) {
                $groups = array_merge($groups, self::fromWarehouse((int) $warehouseId));
            }
        }

        if (!empty($model->sale_id)) {
            $sale = Sale::withoutGlobalScopes()->find($model->sale_id);
            if ($sale) {
                $saleGroups = self::normalizeIds($sale->user_group_ids ?? []);
                if (!empty($saleGroups)) {
                    $groups = array_merge($groups, $saleGroups);
                } else {
                    $groups = array_merge($groups, self::resolveForModel($sale));
                }
            }
        }

        if (!empty($model->purchase_id)) {
            $purchase = Purchase::withoutGlobalScopes()->find($model->purchase_id);
            if ($purchase) {
                $purchaseGroups = self::normalizeIds($purchase->user_group_ids ?? []);
                if (!empty($purchaseGroups)) {
                    $groups = array_merge($groups, $purchaseGroups);
                } else {
                    $groups = array_merge($groups, self::resolveForModel($purchase));
                }
            }
        }

        $groups = self::normalizeIds($groups);

        if (!empty($groups)) {
            return $groups;
        }

        return self::fromAuthUser();
    }

    public static function applyToModel(Model $model): void
    {
        if (self::shouldSkipAutoResolve($model)) {
            if (self::isEmptyGroupIds($model->user_group_ids ?? null)) {
                $model->user_group_ids = self::fromAuthUser();
            }

            return;
        }

        $resolved = self::resolveForModel($model);
        if (!empty($resolved)) {
            $model->user_group_ids = $resolved;
        } elseif (self::isEmptyGroupIds($model->user_group_ids ?? null)) {
            $model->user_group_ids = self::fromAuthUser();
        }
    }

    private static function fromPartyType(string $partyType, int $partyId): array
    {
        $partyType = strtolower(trim($partyType));

        if (in_array($partyType, ['vendor', 'vendors'], true)) {
            return self::fromVendor($partyId);
        }

        if (in_array($partyType, [
            'customer',
            'customers',
            'walkin',
            'walking',
            'walking customer',
            'subcustomer',
            'sub_customer',
            '1',
        ], true)) {
            return self::fromCustomer($partyId);
        }

        return [];
    }

    private static function fromMorph(string $type, int $id): array
    {
        if (str_contains($type, 'Vendor')) {
            return self::fromVendor($id);
        }

        if (str_contains($type, 'Customer')) {
            return self::fromCustomer($id);
        }

        return [];
    }

    private static function fromCustomer(int $id): array
    {
        $customer = Customer::withoutGlobalScopes()->find($id);

        return self::normalizeIds($customer->user_group_ids ?? []);
    }

    private static function fromVendor(int $id): array
    {
        $vendor = Vendor::withoutGlobalScopes()->find($id);

        return self::normalizeIds($vendor->user_group_ids ?? []);
    }

    private static function fromWarehouse(int $id): array
    {
        $warehouse = Warehouse::withoutGlobalScopes()->find($id);

        return self::normalizeIds($warehouse->user_group_ids ?? []);
    }

    private static function fromAccount(int $id): array
    {
        $account = Account::withoutGlobalScopes()->find($id);

        return self::normalizeIds($account->user_group_ids ?? []);
    }

    public static function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
