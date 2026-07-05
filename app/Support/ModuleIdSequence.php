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
    /** Main Head (AccountHead) — shown as Head Code on /view_all */
    public const ACCOUNT_HEAD_MIN = 50000;
    public const ACCOUNT_HEAD_MAX = 59999;
    /** Sub Head (Account) — account_code and primary key, starts after Main Head 50000 */
    public const SUB_HEAD_MIN = 50001;
    public const SUB_HEAD_MAX = 599999;

    /** @deprecated use SUB_HEAD_MIN */
    public const ACCOUNT_MIN = self::SUB_HEAD_MIN;
    /** @deprecated use SUB_HEAD_MAX */
    public const ACCOUNT_MAX = self::SUB_HEAD_MAX;

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

    public static function peekNextSubHeadCode(): string
    {
        return (string) self::resolveNextSubHeadCode(false);
    }

    public static function nextSubHeadCode(): string
    {
        return (string) self::resolveNextSubHeadCode(true);
    }

    public static function resolveNextSubHeadCode(bool $lock = false): int
    {
        $resolver = function () {
            $maxId = DB::table('accounts')
                ->whereBetween('id', [self::SUB_HEAD_MIN, self::SUB_HEAD_MAX])
                ->max('id');

            $maxCode = DB::table('accounts')
                ->whereRaw('account_code REGEXP "^[0-9]+$"')
                ->whereRaw('CAST(account_code AS UNSIGNED) >= ?', [self::SUB_HEAD_MIN])
                ->max(DB::raw('CAST(account_code AS UNSIGNED)'));

            $next = self::SUB_HEAD_MIN;
            if ($maxId) {
                $next = max($next, (int) $maxId + 1);
            }
            if ($maxCode) {
                $next = max($next, (int) $maxCode + 1);
            }

            while (
                DB::table('accounts')->where('account_code', (string) $next)->exists()
                || DB::table('accounts')->where('id', $next)->exists()
            ) {
                $next++;
            }

            if ($next > self::SUB_HEAD_MAX) {
                throw new RuntimeException('No sub head codes left in configured range.');
            }

            return $next;
        };

        return $lock
            ? DB::transaction(fn () => $resolver())
            : $resolver();
    }
}
