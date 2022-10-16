<?php

@include_once __DIR__ . '/vendor/autoload.php';

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
    ],
    'fileMethods' => [
        'fingerprint' => function () {
            return (new \Bnomei\Fingerprint())->process($this)['hash'];
        },
        'integrity' => function () {
            return (new \Bnomei\Fingerprint())->process($this)['integrity'];
        },
    ],
]);
