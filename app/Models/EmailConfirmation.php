<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailConfirmation extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['recipient', 'payload'];

    protected function casts(): array
    {
        return ['recipient' => 'encrypted', 'payload' => 'encrypted:array', 'sent_at' => 'datetime', 'next_attempt_at' => 'datetime', 'attempts' => 'integer'];
    }
}
