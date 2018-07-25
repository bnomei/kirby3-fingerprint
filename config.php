<?php

Kirby::plugin('bnomei/fingerprint', [
  'options' => [
    'hash' => function ($file, $extension) { 
      return $extension . '?v=' . \filemtime($file); 
    }
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
