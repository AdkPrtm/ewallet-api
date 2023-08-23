<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    use HasFactory;

    protected $table = 'transfer_histories';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'transaction_code'
    ];

    protected $cast = [
        'created_at' => 'datetime:Y-m-d H:m:s',
        'updated_at' => 'datetime:Y-m-d H:m:s',
    ];

    public function receiverUser()  {
        return $this->belongsTo(User::class, 'receiver_id', 'id');
    }
}
