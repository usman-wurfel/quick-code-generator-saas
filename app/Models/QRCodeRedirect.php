<?php

namespace App\Models;

use App\Support\DomainManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

/**
 * @property QRCode qrcode
 * @property string route
 * @property string slug
 * @property Domain domain
 */
class QRCodeRedirect extends Model
{
    use HasFactory;

    protected $table = 'qrcode_redirects';

    protected $appends = ['route'];

    public function getRouteAttribute()
    {
        $domain = $this->getDomain();

        $domainManager = new DomainManager;

        return $domainManager->domainUrl($domain, sprintf('/%s', $this->slug));
    }

    public function getDestinationAttribute($value)
    {
        if (preg_match('/^https?/', $value)) return $value;

        return 'http://' . $value;
    }

    public function setDestinationAttribute($value)
    {
        $this->attributes['destination'] = Str::limit($value, 191, '');
    }

    public function qrcode()
    {
        return $this->belongsTo(QRCode::class, 'qrcode_id');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    private function getDomain()
    {
        if ($this->domain) {
            return $this->domain;
        }

        $domainManager = new DomainManager();

        return $domainManager->getDefaultDomain();
    }
}
