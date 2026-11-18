<?php

namespace App\Models;

use App\Events\QRCodeSaved;
use App\Models\Traits\HasMetaFields;
use App\Repositories\FileManager;
use App\Support\QRCodeProcessors\AdvancedShapeProcessors\BaseAdvancedShapeProcessor;
use App\Support\QRCodeProcessors\AdvancedShapeProcessors\CouponAdvancedShape;
use App\Support\QRCodeProcessors\GradientProcessor;
use App\Support\QRCodeStorage;
use App\Support\QRCodeTypes\BaseType;
use App\Support\QRCodeTypes\QRCodeTypeManager;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;


/**
 * @property int id
 * @property User user
 * @property QRCodeRedirect redirect
 * @property bool archived
 * @property string pincode
 */
class QRCode extends Model
{
    use HasMetaFields;
    use HasFactory;

    protected $fillable = ['name', 'type', 'design', 'data', 'folder_id'];

    protected $with = ['logo', 'foreground_image'];

    protected $appends = ['logo_id', 'foreground_image_id', 'svg_url'];

    protected $table = 'qrcodes';

    protected $casts = [
        'data' => 'object',
        'design' => 'object',
        'archived' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'saved' => QRCodeSaved::class,
    ];

    private $white = '#ffffff';

    private $primaryColor = '#1c57cb';

    private $black = '#000000';

    public static function getTypes()
    {
        return (new QRCodeTypeManager)->slugs();
    }

    protected static function booted()
    {
        static::creating([static::class, 'onCreating']);

        static::saving([static::class, 'onSaving']);
    }

    static function onCreating($model)
    {
        $model->generateFileName();
    }

    static function onSaving($model)
    {
        $model->generateNameIfNeeded();
    }

    private function generateNameIfNeeded()
    {
        if (!empty($this->name)) {
            return;
        }

        $type = (new QRCodeTypeManager)->find($this->type);

        $this->name = $type->generateName($this);
    }

    public function generateFileName()
    {
        $fileName = '';

        $found = true;

        do {
            $fileName = uniqid();

            $found = QRCode::where('file_name', $fileName)->first();
        } while ($found);

        $this->file_name = $fileName;
    }

    public function redirect()
    {
        return $this->hasOne(QRCodeRedirect::class, 'qrcode_id');
    }

    public function setDesignAttribute($designParam)
    {
        $designParam = (object) $designParam;

        $defaultDesign = array_merge(
            [
                'fillType' => 'solid',
                'foregroundColor' => $this->black,
                'eyeInternalColor' => $this->black,
                'eyeExternalColor' => $this->black,
                'gradientFill' => GradientProcessor::defaultGradient(),
                'module' => 'square',
                'shape' => 'none',
                'frameColor' => $this->black,
                'logoScale' => 0.2, // default
                'logoPositionX' => 0.5,
                'logoPositionY' => 0.5,
                'logoRotate' => 0,
                'logoBackground' => true,
                'logoBackgroundFill' => $this->white,
                'logoUrl' => null,
                'logoType' => 'preset',
                'logoBackgroundScale' => 1.5,
                'logoBackgroundShape' => 'circle',
                'backgroundEnabled' => true,
                'backgroundColor' => $this->white,
                'finder' => 'default',
                'finderDot' => 'default',
                'advancedShape' => 'none',
                'advancedShapeDropShadow' => true,
                'advancedShapeFrameColor' => $this->black,
                'reviewCollectorCircleColor' => null,
                'reviewCollectorStarsColor' => null,
                'reviewCollectorLogo' => null,
                'reviewCollectorLogoSrc' => null,
                'healthcareFrameColor' => null,
                'healthcareHeartColor' => null,
                'is_ai' => false,
                'ai_prompt' => '',
                'ai_strength' => 0.4,
                'ai_steps' => 18,
            ],
            BaseAdvancedShapeProcessor::defaultTextRelatedValues(),
            CouponAdvancedShape::defaultValues(),
        );

        $design = (object) [];

        if (!$designParam) {
            $designParam = (object) [];
        }

        foreach ($defaultDesign as $key => $defaultValue) {
            $design->$key = !isset($designParam->$key) ? $defaultValue : $designParam->$key;
        }

        $this->attributes['design'] = json_encode($design);
    }

    public function logo()
    {
        return $this
            ->morphOne(File::class, 'attachable')
            ->where(
                'type',
                FileManager::FILE_TYPE_QRCODE_LOGO
            );
    }

    public function foreground_image()
    {
        return $this
            ->morphOne(File::class, 'attachable')
            ->where(
                'type',
                FileManager::FILE_TYPE_QRCODE_FOREGROUND_IMAGE
            );
    }

    public function foregroundImageId(): Attribute
    {
        return new Attribute(fn () => $this->foreground_image?->id);
    }

    public function logoId(): Attribute
    {
        return new Attribute(fn () => $this->logo?->id);
    }

    /**
     * @deprecated
     * @use svg_url()
     */
    public function url()
    {
        return $this->svg_url();
    }

    public function svg_url()
    {
        return QRCodeStorage::ofQRCode($this)->getSvgUrl();
    }

    public function getSvgUrlAttribute()
    {
        return $this->svg_url();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolveType(): ?BaseType
    {
        return (new QRCodeTypeManager)->find($this->type);
    }
}
