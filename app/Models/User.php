<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Carbon\Carbon;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'email',
    ];

    protected $appends = [
        'absolute_cv_url',
        'absolute_image_url'
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function identities()
    {
        return $this->hasMany(SocialIdentity::class);
    }

    public function setting()
    {
        return $this->hasOne(Setting::class, '');
    }

    public function transections()
    {
        return $this->hasMany(Transaction::class, 'receiver_id');
    }

    public function FavouriteArtist()
    {
        return $this->belongsToMany(User::class, 'favourite_artist')->withPivot('artist_id');
    }

    public function getAbsoluteCvUrlAttribute()
    {
        return url($this->attributes['cv_url']);
    }

    public function getAbsoluteImageUrlAttribute()
    {
        return url($this->attributes['image_url']);
    }

    public function jobs()
    {
        return $this->hasMany(Booking::class, 'artist_id');
    }

    public function reviews()
    {
        return $this->hasMany(Rating::class, 'artist_id');
    }

    public function portfolio()
    {
        return $this->hasMany(Portfolio::class, 'artist_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'artist_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
