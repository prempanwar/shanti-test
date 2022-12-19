<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friend extends Model
{
    use HasFactory;
    protected $table = "friends";
    protected $fillable = [
       'user_id'
       'friend_id'
       'request_status'
       'user_id'
    ];

    // public function user(){
    //     return $this->belongsTo("User");
    // }
    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'id', 'friend_id');
    // }
}
