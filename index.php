<?php

use Bnomei\Fingerprint;
use Bnomei\FingerprintFile;
use Kirby\Cms\File;
use Kirby\Cms\FileVersion;

require_once __DIR__.'/classes/Fingerprint.php';
require_once __DIR__.'/classes/FingerprintFile.php';

if (! function_exists('css_f')) {
    function css_f(File|FileVersion|string $url, string|array $attrs = []): ?string
    {
        return (new Fingerprint)->css($url, $attrs);
    }
}

if (! function_exists('js_f')) {
    function js_f(File|FileVersion|string $url, array $attrs = []): ?string
    {
        return (new Fingerprint)->js($url, $attrs);
    }
}

if (! function_exists('url_f')) {
    function url_f(File|FileVersion|string $url): string
    {
        return (new Fingerprint)->url($url);
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
            return (new FingerprintFile($file))->hash($query);
        },
        'integrity' => function ($file, ?string $digest = null, ?string $manifest = null) {
            return (new FingerprintFile($file))->integrity($digest, $manifest);
        },
        'ignore-missing-auto' => true,
        'absolute' => true,
    ],
    'fileMethods' => [
        'fingerprint' => function () {
            return (new Fingerprint)->process($this)['hash'];
        },
        'integrity' => function () {
            return (new Fingerprint)->process($this)['integrity'];
        },
    ],
]);
