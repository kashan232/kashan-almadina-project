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

            $nextId = ModuleIdSequence::nextId($model->getTable(), $range['min'], $range['max']);

            $model->setAttribute($model->getKeyName(), $nextId);

            if (method_exists($model, 'syncModuleCodeFromId')) {
                $model->syncModuleCodeFromId();
            }
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
