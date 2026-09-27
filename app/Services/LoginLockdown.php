<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;

class LoginLockdown
{
    public const SETTING_KEY = 'login_lockdown_enabled';

    public static function isActive(): bool
    {
        return SystemSetting::getBool(self::SETTING_KEY, false);
    }

    public static function setActive(bool $active): void
    {
        SystemSetting::set(self::SETTING_KEY, $active);
    }

    /** Only super admin account (ID 1 or admin@admin.com) may bypass lockdown. */
    public static function canBypass(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return (int) $user->id === 1 || strtolower((string) $user->email) === 'admin@admin.com';
    }
}
