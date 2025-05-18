<?php

use Kirby\Cms\File;
use Kirby\Cms\FileVersion;

@include_once __DIR__.'/vendor/autoload.php';

if (! function_exists('css_f')) {
    function css_f(File|FileVersion|string $url, string|array $attrs = []): ?string
    {
        return (new \Bnomei\Fingerprint)->css($url, $attrs);
    }
}

if (! function_exists('js_f')) {
    function js_f(File|FileVersion|string $url, array $attrs = []): ?string
    {
        return (new \Bnomei\Fingerprint)->js($url, $attrs);
    }
}

if (! function_exists('url_f')) {
    function url_f(File|FileVersion|string $url): string
    {
        return (new \Bnomei\Fingerprint)->url($url);
    }
}

Kirby::plugin('bnomei/fingerprint', [
    'options' => [
        'cache' => true,
        'query' => true,
        'digest' => 'sha384',
        'https' => function () {
            return kirby()->system()->isLocal() === false;
        },
        'hash' => function ($file, $query = true) {
            return (new \Bnomei\FingerprintFile($file))->hash($query);
        },
        'integrity' => function ($file, ?string $digest = null, ?string $manifest = null) {
            return (new \Bnomei\FingerprintFile($file))->integrity($digest, $manifest);
        },
        'ignore-missing-auto' => true,
        'absolute' => true,
    ],
    'fileMethods' => [
        'fingerprint' => function () {
            return (new \Bnomei\Fingerprint)->process($this)['hash'];
        },
        'integrity' => function () {
            return (new \Bnomei\Fingerprint)->process($this)['integrity'];
        },
    ],
]);
