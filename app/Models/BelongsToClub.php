<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToClub
{
    protected static function bootBelongsToClub(): void
    {
        static::addGlobalScope('club', function (Builder $query) {
            $clubId = Auth::user()?->club_id;
            if ($clubId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where($query->getModel()->qualifyColumn('club_id'), $clubId);
            }
        });

        static::creating(function (Model $model) {
            $clubId = Auth::user()?->club_id;
            abort_unless($clubId && (! $model->club_id || (int) $model->club_id === (int) $clubId), 403);
            $model->club_id = $clubId;
        });
        static::updating(function (Model $model) {
            abort_if($model->isDirty('club_id'), 403);
        });
    }
}
