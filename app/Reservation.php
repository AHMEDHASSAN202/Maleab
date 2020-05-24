<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $guarded = [];

    protected $dates = ['start_at', 'end_date'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function playground()
    {
        return $this->belongsTo(User::class, 'playground_id');
    }
}
