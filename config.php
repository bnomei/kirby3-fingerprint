<?php

Kirby::plugin('bnomei/fingerprint', [
  'options' => [
    'hash' => null, // defaults to: function ($file) { return \filemtime($file); }
  ],
  'fileMethods' => [
    'fingerprint' => function ($file) {
      return $file->url();
      if($file) {
        return \Bnomei\Fingerprint::injectFileMTime($file->url());
      }
      return null;
    }
  ]
]);
