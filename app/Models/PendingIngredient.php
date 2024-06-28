<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingIngredient extends Model
{
    use HasFactory;

    protected $fillable = ['ingredient_name', 'required_quantity'];
}
