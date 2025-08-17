<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'currency',
        'payment_gateway',
        'transaction_id',
        'status',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}