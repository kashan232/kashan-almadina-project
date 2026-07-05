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

    /** Admin accounts that may login while lockdown is active. */
    public static function canBypass(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->email === 'admin@admin.com' || $user->usertype === 'admin') {
            return true;
        }

        return $user->hasRole('Admin');
    }
}
