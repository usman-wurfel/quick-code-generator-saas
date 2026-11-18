<?php

namespace App\Support\QRCodeTypes\ViewComposers;

use App\Models\File;
use App\Support\Color;
use App\Support\QRCodeStorage;
use App\Support\QRCodeTypes\ViewComposers\Traits\GeneratesGradientBackground;
use App\Support\QRCodeTypes\ViewComposers\Traits\HasBusinessHours;
use App\Support\QRCodeTypes\ViewComposers\Traits\HasSocialIcons;
use App\Support\QRCodeTypes\ViewComposers\Traits\HasWhiteCards;
use Illuminate\Support\Str;

class VCardPlus extends Base
{
    use HasWhiteCards;
    use HasSocialIcons;
    use GeneratesGradientBackground;
    use HasBusinessHours;

    public static function type()
    {
        return 'vcard-plus';
    }

    protected function templateColors($key)
    {
        $template = $this->design?->value('businessType', 'bakery') ?? 'bakery';

        return [
            'bakery' => [
                'backgroundColor' => '#ff8929',
                'iconsColor' => '#ff8929',
                'textColor' => '#ffffff',
            ],
            'healthcare' => [
                'backgroundColor' => '#1fceb5',
                'iconsColor' => '#668fb0',
                'textColor' => '#ffffff',
            ],
            'restaurant' => [
                'backgroundColor' => '#fd4921',
                'iconsColor' => '#9f4f2c',
                'textColor' => '#ffffff',
            ],
            'plumber' => [
                'backgroundColor' => '#ffde38',
                'iconsColor' => '#db0000',
                'textColor' => '#39372d',
            ],
            'barber' => [
                'backgroundColor' => '#ffd780',
                'iconsColor' => '#4c3d00',
                'textColor' => '#4c3d00'
            ],
            'electrician' => [
                'backgroundColor' => '#fbfbe9',
                'iconsColor' => '#db2424',
                'textColor' => '#342d2d'
            ],
            'builder' => [
                'backgroundColor' => '#ffce00',
                'iconsColor' => '#007fb0',
                'textColor' => '#212121'
            ],
            'gardener' => [
                'backgroundColor' => '#58bd00',
                'iconsColor' => '#8c5f12',
                'textColor' => '#ffffff'
            ],
            'cafe' => [
                'backgroundColor' => '#daa669',
                'iconsColor' => '#925817',
                'textColor' => '#fdf7ed'
            ],
            'mechanic' => [
                'backgroundColor' => '#323950',
                'iconsColor' => '#5c71c7',
                'textColor' => '#ffffff'
            ],
            'garage' => [
                'backgroundColor' => '#21222e',
                'iconsColor' => '#ff616d',
                'textColor' => '#ededed'
            ],
            'joiner' => [
                'backgroundColor' => '#b38759',
                'iconsColor' => '#f4af46',
                'textColor' => '#f5f5f5'
            ],
            'car-valeter' => [
                'backgroundColor' => '#d7d7d7',
                'iconsColor' => '#b93d49',
                'textColor' => '#413535'
            ],
            'painter' => [
                'backgroundColor' => '#9e9e9e',
                'iconsColor' => '#f0d111',
                'textColor' => '#eeede9'
            ],
            'plaster' => [
                'backgroundColor' => '#7c7b64',
                'iconsColor' => '#6e6d59',
                'textColor' => '#fffafa'
            ],
            'cleaner' => [
                'backgroundColor' => '#009ec8',
                'iconsColor' => '#0059c2',
                'textColor' => '#e0eaf5'
            ],
            'roofer' => [
                'backgroundColor' => '#8bb1dc',
                'iconsColor' => '#3c5f87',
                'textColor' => '#ededed'
            ],
            'accountant' => [
                'backgroundColor' => '#dbe1e8',
                'iconsColor' => '#749692',
                'textColor' => '#352e2e'
            ],
            'solicitor' => [
                'backgroundColor' => '#7f5a28',
                'iconsColor' => '#77592d',
                'textColor' => '#fff8f5'
            ],
            'other' => [
                'backgroundColor' => '#6ea9cf',
                'iconsColor' => '#4842aa',
                'textColor' => '#ffffff',
            ],
        ][$template][$key];
    }

    protected function getTemplate()
    {
        return $this->designValue('businessType', 'bakery');
    }

    public function qrcodeUrl()
    {
        return QRCodeStorage::ofQRCode($this->qrcode)->getTemporaryDirectUrl();
    }

    public function gradientBgStyles()
    {
        return $this->generateGradientBackground(
            '.qrcode-type-vcard-plus .details-container .gradient-bg',
            'backgroundColor'
        );
    }

    public function addContactButtonStyles()
    {
        $color = $this->designValue('addContactButtonColor');

        if (!$color) return;

        return sprintf(
            '%s { background-color: %s; }',
            $this->addToContactButtonSelectors(),
            $color
        );
    }

    private function addToContactButtonSelectors()
    {
        return '.qrcode-type-vcard-plus .button.add-contact, .qrcode-type-vcard-plus .button.add-contact.floating';
    }

    public function addContactButtonTextStyles()
    {
        $color = $this->designValue('addContactButtonTextColor');

        if (!$color) return;

        return sprintf(
            '%s { color: %s; }',
            $this->addToContactButtonSelectors(),
            $color
        );
    }

    public function secondBackgroundStyles()
    {
        return $this->generateGradientBackground(
            '.qrcode-type-vcard-plus .details-container',
            'secondBackgroundColor'
        );
    }

    public function textColorsStyles()
    {
        $color = $this->designValue('textColor');

        if (empty($color)) {
            return null;
        }

        $selector = 'html .qrcode-type-vcard-plus .vertical-list';

        $pattern = "$selector { color: %s; }";

        return sprintf(
            $pattern,
            $this->designValue('textColor', Color::getContrastColor($color))
        );
    }

    public function contacts()
    {
        $defaultContacts = [
            [
                'name' => t('Mobile'),
                'value' => $this->qrcodeData('mobile'),
                'link' => 'tel:' . $this->qrcodeData('mobile'),
            ],
            [
                'name' => t('Email'),
                'value' => $this->qrcodeData('email'),
                'link' => 'mailto:' . $this->qrcodeData('email'),
            ],
            [
                'name' => t('Work'),
                'value' => $this->qrcodeData('phone'),
                'link' => 'tel:' . $this->qrcodeData('phone'),
            ],
        ];

        $customContacts = collect(
            $this->qrcodeData('contactFields', [])
        )
            ->map(fn ($c) => (array) $c)->all();

        $allContacts = array_merge($defaultContacts, $customContacts);

        return $allContacts;
    }

    public function whatsappNumber()
    {
        $number = $this->qrcodeData('whatsapp_number');

        $number = preg_replace("/[^0-9]/", "", $number);

        return sprintf('https://wa.me/%s', $number);
    }

    public function websites()
    {
        $websites = $this->qrcodeData('websites', '');

        $websites = explode("\n", $websites);

        return collect($websites)
            ->filter(
                fn ($w) => !empty(trim($w))
            )->map(function ($w) {
                if (!Str::startsWith($w, 'http')) {
                    $w = "http://$w";
                }
                return $w;
            })
            ->values();
    }

    public function hasMapsUrl()
    {
        return !empty($this->qrcodeData('maps_url'));
    }

    public function address()
    {
        return trim(
            sprintf(
                '%s %s %s %s %s',
                $this->qrcodeData('street', ''),
                $this->qrcodeData('zip', ''),
                $this->qrcodeData('city', ''),
                $this->qrcodeData('state', ''),
                $this->qrcodeData('country', ''),
            )
        );
    }

    public function shouldRenderQRCode()
    {
        $value = $this->designValue('qrcode_preference');

        return empty($value) || $value === 'show';
    }

    public function shouldRenderQRCodeFrame()
    {
        return $this->shouldRenderQRCode() || $this->shouldRenderLogo();
    }

    public function shouldRenderLogo()
    {
        $value = $this->designValue('qrcode_preference');

        return $value == 'logo' && !empty($this->fileUrl('logo'));
    }

    public function logoBackgroundStyles()
    {
        $value = $this->designValue('logo_background');

        if (empty($value) || $value === 'square') {
            return;
        }

        if ($value === 'none') {
            $rule = 'background-color: transparent; padding: 0; box-shadow: none;';
        }

        if ($value === 'round') {
            $rule = 'border-radius: 50%; padding: 2.3rem;';
        }


        $selector = '.qrcode-type-vcard-plus .qrcode-container';


        return sprintf('%s { %s }', $selector, $rule);
    }

    public function portfolio()
    {
        $images = $this->designValue('images');

        if (empty($images)) return [];

        if (!is_array($images)) {
            $images = [$images];
        }

        $result = [];

        foreach ($images as $fileId) {
            $src = $this->files->url(File::find($fileId));

            $title = $this->designValue(
                sprintf('image_%s_caption', $fileId)
            );

            $link = $this->designValue(
                sprintf('image_%s_link', $fileId)
            );

            $description = $this->designValue(
                sprintf('image_%s_description', $fileId)
            );

            $sort_order = $this->designValue(
                sprintf('image_%s_sort_order', $fileId)
            );

            $result[] = compact('src', 'title', 'link', 'description', 'sort_order');
        }

        usort($result, function ($a, $b) {
            $s1 = @$a['sort_order'] != null ? @$a['sort_order'] : 100;
            $s2 = @$b['sort_order'] != null ? @$b['sort_order'] : 100;

            return $s1 - $s2;
        });

        return $result;
    }

    public function portfolioTitleStyles()
    {
        $color = $this->designValue('portfolio_section_title_color');

        if (empty($color)) return;

        return sprintf('.qrcode-type-vcard-plus .portfolio-title { color: %s; }', $color);
    }

    public function shouldRenderPortfolio()
    {
        $images = $this->designValue('images');

        return !empty($images);
    }

    private function logoBase64()
    {
        if (!$this->shouldRenderLogo()) return;

        $file = File::find($this->designValue('logo'));

        $ext = $this->files->extension($file);

        $ext = strtoupper($ext);

        return sprintf(
            'ENCODING=BASE64;TYPE=%s:%s',
            $ext,
            $this->files->base64($file)
        );
    }

    public function vcard()
    {
        $firstName = $this->qrcodeData('firstName');
        $lastName = $this->qrcodeData('lastName');
        $mobile = $this->qrcodeData('mobile');
        $phone = $this->qrcodeData('phone');
        $email = $this->qrcodeData('email');
        $company = $this->qrcodeData('company');
        $job = $this->qrcodeData('job');
        $street = $this->qrcodeData('street');
        $city = $this->qrcodeData('city');
        $state = $this->qrcodeData('state');
        $zip = $this->qrcodeData('zip');
        $country = $this->qrcodeData('country');

        $website = $this->qrcode->redirect->route;

        $bio = $this->qrcodeData('bio');

        $sanitize = function ($str) {
            $str = str_replace(";", '\;', $str);
            $str = str_replace(",", '\,', $str);
            $str = str_replace("\n", " ", $str);
            $str = str_replace("'", "\'", $str);
            return $str;
        };

        foreach (get_defined_vars() as $var => $value) {
            if ($var === 'sanitize') continue;

            $$var = $sanitize($value);
        }

        $logo = $this->logoBase64();

        $logoField = $logo ? "PHOTO;$logo" : null;

        $vcard = <<<END_VCARD
BEGIN:VCARD
VERSION:3.0
$logoField
N:$lastName;$firstName;;;
FN:$firstName $lastName
TITLE:$job
ORG:$company
URL:$website
EMAIL;WORK;INTERNET:$email
TEL;WORK;VOICE:$phone
TEL;CELL:$mobile
ADR:;;$street;$city;$state;$zip;$country
NOTE:$bio
END:VCARD
END_VCARD;

        return $vcard;
    }

    public function vcardFileName()
    {
        return Str::kebab($this->qrcodeData('firstName') . ' ' . $this->qrcodeData('lastName') . '.vcard');
    }

    public function customLinkTarget($link)
    {
        $value = @$link['target'];

        if (empty($value)) return;

        if ($value === 'self') return;

        return 'target="_blank"';
    }

    public function customLinks()
    {
        $links = $this->designValue('customLinks');

        if (empty($links) || !is_array($links)) return [];

        $sorted = collect($links)->sort(function ($c1, $c2) {
            $s1 = is_numeric(@$c1['sort_order']) ? @$c1['sort_order'] : 0;
            $s2 = is_numeric(@$c2['sort_order']) ? @$c2['sort_order'] : 0;

            return $s1 - $s2;
        });

        return $sorted;
    }

    public function faqs()
    {
        $items = $this->designValue('faqs');

        if (empty($items) || !is_array($items)) return [];

        $sorted = collect($items)->sort(function ($c1, $c2) {
            $s1 = is_numeric(@$c1['sort_order']) ? @$c1['sort_order'] : 0;
            $s2 = is_numeric(@$c2['sort_order']) ? @$c2['sort_order'] : 0;

            return $s1 - $s2;
        });

        return $sorted;
    }

    public function faqsMainTitle()
    {
        $default = t('FAQs');

        $value = $this->designValue('faqs_main_title');

        if (empty($value)) return $default;

        return $value;
    }

    public function customLinkStyles()
    {
        $links = $this->designValue('customLinks');

        if (empty($links) || !is_array($links)) return;

        $selector = '.qrcode-type-vcard-plus .custom-links .custom-link';

        $rules = array_map(function ($link) use ($selector) {

            $rules = [];

            if (!empty(@$link['color'])) {
                $rules[] = sprintf('background-color: %s;', $link['color']);
                $rules[] = sprintf('border: 0;');
            }

            if (!empty(@$link['text_color'])) {
                $rules[] = sprintf('color: %s;', $link['text_color']);
            }

            return sprintf(
                '%s.%s { %s }',
                $selector,
                $link['id'],
                implode(' ', $rules)
            );
        }, $links);


        return implode("\n", $rules);
    }

    public function shouldRenderSplashScreen()
    {
        $enabled = $this->designValue('splash_screen_enabled') === 'enabled';

        return $enabled && !empty($this->fileUrl('splash_screen_logo'));
    }

    public function shouldShowContactDetails()
    {
        $value = $this->designValue('contacts_settings');

        if (empty($value)) return true;

        return $value === 'both' || $value === 'details';
    }

    public function shouldShowContactIcons()
    {
        $value = $this->designValue('contacts_settings');

        if (empty($value)) return true;

        return $value === 'both' || $value === 'icons';
    }
}
