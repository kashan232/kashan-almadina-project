<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ModuleIdSequence
{
    public const CUSTOMER_MAIN_MIN = 10001;
    public const CUSTOMER_MAIN_MAX = 19999;
    public const CUSTOMER_WALKIN_MIN = 20001;
    public const CUSTOMER_WALKIN_MAX = 29999;
    public const VENDOR_MIN = 30001;
    public const VENDOR_MAX = 39999;
    public const PRODUCT_MIN = 40001;
    public const PRODUCT_MAX = 49999;
    public const ACCOUNT_MIN = 50001;
    public const ACCOUNT_MAX = 59999;

    public static function nextId(string $table, int $min, int $max): int
    {
        return DB::transaction(function () use ($table, $min, $max) {
            $maxInRange = DB::table($table)
                ->whereBetween('id', [$min, $max])
                ->lockForUpdate()
                ->max('id');

            $next = $maxInRange ? ((int) $maxInRange + 1) : $min;

            if ($next > $max) {
                throw new RuntimeException("No IDs left in range {$min}-{$max} for table {$table}.");
            }

            return $next;
        });
    }

    public static function peekNextId(string $table, int $min, int $max): int
    {
        $maxInRange = DB::table($table)
            ->whereBetween('id', [$min, $max])
            ->max('id');

        $next = $maxInRange ? ((int) $maxInRange + 1) : $min;

        return min($next, $max);
    }

    public static function customerRange(?string $customerType): array
    {
        $isWalkIn = strtolower(trim($customerType ?? '')) === 'walking customer';

        return $isWalkIn
            ? ['min' => self::CUSTOMER_WALKIN_MIN, 'max' => self::CUSTOMER_WALKIN_MAX]
            : ['min' => self::CUSTOMER_MAIN_MIN, 'max' => self::CUSTOMER_MAIN_MAX];
    }
}
