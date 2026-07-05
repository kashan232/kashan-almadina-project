<?php

namespace App\Traits;

use App\Support\ModuleIdSequence;
use Illuminate\Database\Eloquent\Model;

trait HasModuleIdSequence
{
    public static function bootHasModuleIdSequence(): void
    {
        static::creating(function (Model $model) {
            if ($model->getKey()) {
                return;
            }

            $range = $model->resolveModuleIdRange();

            $model->setAttribute(
                $model->getKeyName(),
                ModuleIdSequence::nextId($model->getTable(), $range['min'], $range['max'])
            );
        });
    }

    /** @return array{min: int, max: int} */
    protected function resolveModuleIdRange(): array
    {
        return static::defaultModuleIdRange();
    }

    /** @return array{min: int, max: int} */
    protected static function defaultModuleIdRange(): array
    {
        throw new \LogicException(static::class . ' must define defaultModuleIdRange().');
    }
}
