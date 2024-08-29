<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','receiver_id','message','image','voice'];


    // public function user(){
    //     return $this->belongsTo(User::class);
    // }
    public function sender()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to the receiver
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
