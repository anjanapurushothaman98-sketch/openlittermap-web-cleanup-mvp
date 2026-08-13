<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marker extends Model
{
    protected $fillable = ['lat', 'lng', 'status', 'description', 'photo', 'creator', 'litter_type', 'weight_kg'];
}