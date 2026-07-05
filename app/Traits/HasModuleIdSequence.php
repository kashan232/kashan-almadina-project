<?php

namespace App\Traits;

use App\Models\Account;
use App\Models\AccountHead;
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

            if ($model instanceof AccountHead) {
                $nextId = ModuleIdSequence::nextMainHeadId();
                $model->setAttribute($model->getKeyName(), $nextId);

                return;
            }

            if ($model instanceof Account && $model->shouldUseSubHeadCodeRange()) {
                $headId = (int) $model->head_id;
                $nextId = ModuleIdSequence::resolveNextSubHeadCodeForHead($headId, true);
                $model->setAttribute($model->getKeyName(), $nextId);
                if (empty($model->account_code)) {
                    $model->account_code = (string) $nextId;
                }

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
