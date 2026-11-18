<?php

use App\Repositories\TranslationManager;

function t(string $text)
{
    return TranslationManager::t($text);
}

function frontend_custom_url($path = null)
{
    $url = json_decode(config('app.frontend_custom_url'));

    if (!empty($path)) {
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
    }

    return $url ? $url . $path : null;
}

function asset_with_version_query_param($relativePath)
{
    try {
        $v = filemtime(public_path($relativePath));

        return asset("$relativePath?v=$v");
    } catch (Throwable $th) {
        return asset($relativePath);
    }
}

function override_asset($url, $addVersionQueryParam = false)
{
    $path = explode('?', $url)[0];

    $overridePath = str_replace('//', '/', '/override/' . $path);

    $returnUrl = function ($url) use ($addVersionQueryParam) {
        if ($addVersionQueryParam) {
            return asset_with_version_query_param($url);
        }

        return asset($url);
    };

    if (file_exists(public_path($overridePath))) {
        return $returnUrl($overridePath);
    }

    return $returnUrl($url);
}


function override_asset_path($path)
{
    $overridePath = str_replace('//', '/', '/override/' . $path);

    if (file_exists(public_path($overridePath))) {
        return public_path($overridePath);
    }

    return public_path($path);
}
