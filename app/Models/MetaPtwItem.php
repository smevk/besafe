<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaPtwItem extends Model
{
    use HasFactory;
    protected $fillable = ['ptw_item_title', 'group_name'];

}