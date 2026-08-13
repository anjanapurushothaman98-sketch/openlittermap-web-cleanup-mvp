<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bin extends Model
{
    protected $fillable = ['lat', 'lng', 'status', 'description', 'creator'];
}