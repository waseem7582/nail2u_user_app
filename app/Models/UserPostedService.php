<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPostedService extends Model
{
    use HasFactory;

    public function Client()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}