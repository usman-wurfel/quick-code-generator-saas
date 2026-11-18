<?php

namespace App\Support\ViewComposers;

class SocialLinksComposer extends BaseComposer
{

    public static function path(): string
    {
        return 'blue.components.social-links';
    }

    private function getHostname($url)
    {
        $parts = parse_url($url);

        if (!isset($parts['host'])) {
            return null;
        }

        $host = $parts['host'];

        return $host;
    }

    private function mapSocialIdAlias($socialId)
    {
        $aliases = [
            'wa' => 'whatsapp'
        ];

        if (collect(array_keys($aliases))->first(fn ($alias) => $alias == $socialId)) {
            return $aliases[$socialId];
        }

        return $socialId;
    }

    private function removeExtension($host)
    {
        $host = str_replace('www.', '', $host);

        $host = explode('.', $host)[0];

        return $host;
    }

    private function guessSocialId($url)
    {
        $host = $this->getHostname($url);

        $socialId = $this->removeExtension($host);

        return $this->mapSocialIdAlias($socialId);
    }

    private function socialIconFound($socialId)
    {
        return file_exists(
            base_path(
                "resources/views/blue/components/icons/social/$socialId.blade.php"
            )
        );
    }

    public function socialLinks()
    {
        $data = $this->view->getData();

        if (!isset($data['urls'])) {
            return [];
        }

        $urls = $data['urls'];

        if (empty($urls) || !is_string($urls)) {
            return [];
        }

        $urls = explode("\n", $urls);

        return collect($urls)
            ->map(fn ($url) => trim($url))
            ->filter(fn ($url) => !empty($url))
            ->filter(fn ($url) => filter_var($url, FILTER_VALIDATE_URL))
            ->map(function ($url) {
                return [
                    'socialId' => $this->guessSocialId($url),
                    'url' => $url,
                ];
            })->filter(function ($link) {
                return $this->socialIconFound($link['socialId']);
            });
    }
}
