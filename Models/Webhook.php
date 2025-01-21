<?php

namespace App\Containers\Vendor\Anvil\Models;

use App\Ship\Parents\Models\Model;

class Webhook extends Model
{
    protected $fillable = [
      'event',
      'url',
      'secret'
    ];

    protected $hidden = [
        'secret'
    ];
}