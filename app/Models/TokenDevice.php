<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenDevice extends Model
{
    use HasFactory;

    protected $table = 'token_device';

    protected $fillable = [
        'user_id',
        'token_device'
    ];
    
}
