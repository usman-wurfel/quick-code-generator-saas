<?php

namespace App\Models;

use App\Events\UserCreated;
use App\Models\Traits\HasMetaFields;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use App\Support\Auth\AuthManager;
use App\Support\MobileNumberManager;
use App\Support\Sms\Contracts\HasMobileNumber;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;


/**
 * @property string email
 * @property string mobile_iso_code
 * @property string mobile_calling_code
 * @property string mobile_number
 * @property integer parent_id
 * @property User parent_user
 * 
 * @property Collection subscriptions
 * @property Collection roles
 * @property Collection transactions
 * @property Collection qrcodes
 * @property Collection permissions
 * @property Collection sub_users
 */
class User extends Authenticatable implements MustVerifyEmail, HasMobileNumber
{
    use HasApiTokens, HasFactory, Notifiable, HasMetaFields;

    static $count = 0;

    protected $with = ['roles', 'roles.permissions'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_number'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => UserCreated::class,
    ];

    public function setMobileNumberAttribute($value)
    {
        if (!is_array($value) || empty($value)) {
            return;
        }

        $keys = ['iso_code', 'mobile_number'];

        foreach ($keys as $key) {
            if (empty($value[$key])) {
                return;
            }
        }

        $mobileNumberManager = new MobileNumberManager;

        $this->attributes['mobile_iso_code'] = $value['iso_code'];
        $this->attributes['mobile_calling_code'] = $mobileNumberManager->callingCodeByIsoCode($value['iso_code']);
        $this->attributes['mobile_number'] = $value['mobile_number'];
    }

    public function getMobileNumberAttribute()
    {
        return [
            'mobile_number' => $this->attributes['mobile_number'],
            'iso_code' => $this->mobile_iso_code
        ];
    }

    public function getFormattedMobileNumber(): string
    {
        $code = @$this->attributes['mobile_calling_code'];

        $number = @$this->attributes['mobile_number'];

        if (empty($number)) return '';

        $number = trim($number);

        $number = str_replace(' ', '', $number);

        $number = ltrim($number, '0');

        return "+$code$number";
    }

    public function isSuperAdmin()
    {
        $superAdmin = $this->roles->reduce(
            fn ($superAdmin, $role) => $superAdmin || $role->super_admin,
            false
        );

        if ($superAdmin) return true;
    }

    /**
     * Determines if the user has a permission
     * 
     * @param string $slug Permission slug
     * @return boolean
     */
    public function permitted($slug)
    {
        $model = explode('.', $slug)[0];

        $capability = explode('.', $slug)[1];

        if (!preg_match("/-/", $model)) {
            $model = Str::kebab($model);

            $slug = "$model.$capability";
        }

        if ($this->isSuperAdmin()) return true;

        return $this->permissions->first(function ($permission) use ($slug) {
            return $permission->slug === $slug;
        }) ? true : false;
    }

    public function sendEmailVerificationNotification()
    {
        if (AuthManager::emailVerificationEnabled())
            $this->notify(new VerifyEmail);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    public function permissions(): Attribute
    {
        $permissions = $this
            ->roles
            ->pluck('permissions')
            ->flatten()
            ->unique('id');

        return new Attribute(
            fn () => $permissions
        );
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class)->orderBy('id', 'desc');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Subscription::class);
    }

    public function qrcodes()
    {
        return $this->hasMany(QRCode::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function parent_user()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function sub_users()
    {
        return $this->hasMany(User::class, 'parent_id');
    }
}
