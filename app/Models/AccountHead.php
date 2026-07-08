<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountHead extends Model
{
    use HasFactory, \App\Traits\HasModuleIdSequence, \App\Traits\FiltersInactiveRecords;

    protected $fillable = [
        'name',
        'status',
    ];

    protected static function defaultModuleIdRange(): array
    {
        return [
            'min' => \App\Support\ModuleIdSequence::ACCOUNT_HEAD_MIN,
            'max' => \App\Support\ModuleIdSequence::ACCOUNT_HEAD_MAX,
        ];
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'head_id');
    }
}
