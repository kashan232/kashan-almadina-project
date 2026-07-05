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
    /** Main Head (AccountHead) — 50000, 60000, 70000 … (step 10000) */
    public const ACCOUNT_HEAD_MIN = 50000;
    public const ACCOUNT_HEAD_STEP = 10000;
    public const ACCOUNT_HEAD_MAX = 990000;
    /** Sub Head (Account) — under head H: H+1, H+2 … up to H+9999 */
    public const SUB_HEAD_MIN = 50001;
    public const SUB_HEAD_MAX = 999999;

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

    public static function peekNextMainHeadId(): int
    {
        return self::resolveNextMainHeadId(false);
    }

    public static function nextMainHeadId(): int
    {
        return self::resolveNextMainHeadId(true);
    }

    public static function resolveNextMainHeadId(bool $lock = false): int
    {
        $resolver = function () {
            $blockMax = DB::table('account_heads')
                ->where('id', '>=', self::ACCOUNT_HEAD_MIN)
                ->whereRaw('MOD(id, ?) = 0', [self::ACCOUNT_HEAD_STEP])
                ->max('id');

            if (!$blockMax) {
                return self::ACCOUNT_HEAD_MIN;
            }

            $next = (int) $blockMax + self::ACCOUNT_HEAD_STEP;

            if ($next > self::ACCOUNT_HEAD_MAX) {
                throw new RuntimeException('No main head IDs left in configured range.');
            }

            while (DB::table('account_heads')->where('id', $next)->exists()) {
                $next += self::ACCOUNT_HEAD_STEP;
                if ($next > self::ACCOUNT_HEAD_MAX) {
                    throw new RuntimeException('No main head IDs left in configured range.');
                }
            }

            return $next;
        };

        return $lock
            ? DB::transaction(fn () => $resolver())
            : $resolver();
    }

    public static function subHeadRangeForMainHead(int $headId): array
    {
        $headId = (int) $headId;

        return [
            'min' => $headId + 1,
            'max' => $headId + self::ACCOUNT_HEAD_STEP - 1,
        ];
    }

    public static function peekNextSubHeadCodeForHead(int $headId): string
    {
        return (string) self::resolveNextSubHeadCodeForHead($headId, false);
    }

    public static function nextSubHeadCodeForHead(int $headId): string
    {
        return (string) self::resolveNextSubHeadCodeForHead($headId, true);
    }

    public static function resolveNextSubHeadCodeForHead(int $headId, bool $lock = false): int
    {
        $headId = (int) $headId;
        $range = self::subHeadRangeForMainHead($headId);
        $min = $range['min'];
        $max = $range['max'];

        $resolver = function () use ($headId, $min, $max) {
            $maxId = DB::table('accounts')
                ->where('head_id', $headId)
                ->whereBetween('id', [$min, $max])
                ->max('id');

            $maxCode = DB::table('accounts')
                ->where('head_id', $headId)
                ->whereRaw('account_code REGEXP "^[0-9]+$"')
                ->whereRaw('CAST(account_code AS UNSIGNED) BETWEEN ? AND ?', [$min, $max])
                ->max(DB::raw('CAST(account_code AS UNSIGNED)'));

            $next = $min;
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

            if ($next > $max) {
                throw new RuntimeException("No sub head codes left for main head {$headId}.");
            }

            return $next;
        };

        return $lock
            ? DB::transaction(fn () => $resolver())
            : $resolver();
    }
}
