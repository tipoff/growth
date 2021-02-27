<?php

declare(strict_types=1);

namespace Tipoff\Authorization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use SoftDeletes;
    use Notifiable;
    use HasRoles;
    use HasApiTokens;
    use Billable;

    protected $guarded = ['id'];


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->password)) {
                $user->password = bcrypt(Str::upper(Str::random(8)));
            }
        });
    }

    public function locations()
    {
        return $this->belongsToMany(app('location'))->withTimestamps();
    }

    public function managedLocations()
    {
        return $this->hasMany(app('location'), 'manager_id');
    }

    public function customers()
    {
        return $this->hasMany(app('customer'));
    }

    public function participants()
    {
        return $this->hasMany(app('participant'));
    }

    public function notes()
    {
        return $this->morphMany(app('note'), 'noteable');
    }

    public function contacts()
    {
        return $this->hasMany(app('contact'));
    }

    public function posts()
    {
        return $this->hasMany(app('post'), 'author_id');
    }

    public function blocks()
    {
        return $this->hasMany(app('block'), 'creator_id');
    }

    public function notesCreated()
    {
        return $this->hasMany(app('note'), 'creator_id');
    }

    public function vouchersCreated()
    {
        return $this->hasMany(app('voucher'), 'creator_id');
    }

    public function carts()
    {
        return $this->hasMany(app('cart'));
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->name_last}";
    }

    public function toCustomer(array $attributes): Customer
    {
        $this->assignRole('Customer');

        return $this->customers()->create($attributes);
    }

    /**
     * Get active cart.
     *
     * @return Cart
     */
    public function cart()
    {
        $activeCart = $this->carts()
            ->active()
            ->orderByDesc('id')
            ->first();

        if (empty($activeCart)) {
            $activeCart = $this->carts()->create();
        }

        return $activeCart;
    }
}
