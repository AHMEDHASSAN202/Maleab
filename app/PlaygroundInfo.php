<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PlaygroundInfo extends Model
{
    protected $table = 'playground_info';

    protected $guarded = [];

    public function isClose() {
        return $this->status === 'close';
    }
}
