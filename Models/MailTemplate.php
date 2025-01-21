<?php

namespace App\Containers\Vendor\Anvil\Models;

use App\Ship\Parents\Models\Model;

class MailTemplate extends Model
{
    protected $fillable = [
      'name',
      'subject',
      'body',
      'settings',
      'active'
    ];
}