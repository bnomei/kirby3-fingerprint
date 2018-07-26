<?php

Kirby::plugin('bnomei/fingerprint', [
  'options' => [
    'cache' => true,
    'hash' => function ($file) {
        $url = null;
        $fileroot = is_a($file, 'File') ? $file->root() : kirby()->roots()->index() . DIRECTORY_SEPARATOR . ltrim(str_replace(kirby()->site()->url(), '', $file), '/');

        if (\Kirby\Toolkit\F::exists($fileroot)) {
            $filename = implode('.', [
                \Kirby\Toolkit\F::name($fileroot),
                \Kirby\Toolkit\F::extension($fileroot) . '?v=' . \filemtime($fileroot)
            ]);

            $dirname = \dirname($file);
            $url = ($dirname === '.') ? $filename : ($dirname . '/' . $filename);
        } else {
            $url = $file;
        }
        return \url($url);
    },
    'integrity' => function ($file) {
        $sri = null;
        $file = is_a($file, 'File') ? $file->root() : kirby()->roots()->index() . DIRECTORY_SEPARATOR . ltrim(str_replace(kirby()->site()->url(), '', $file), '/');

        if (!\Kirby\Toolkit\F::exists($file)) {
            return null;
        }

        if (extension_loaded('openssl')) {
            // https://www.srihash.org/
            exec("openssl dgst -sha384 -binary ${file} | openssl base64 -A", $output, $return);
            if (is_array($output) && count($output) >= 1) {
                $sri = 'sha384-'.$output[0];
            }
        } else {
            exec("shasum -b -a 384 ${file} | xxd -r -p | base64", $output, $return);
            if (is_array($output) && count($output) >= 1) {
                $sri = 'sha384-'.$output[0];
            }
        }
        return $sri;
    }
  ],
  'fileMethods' => [
    'fingerprint' => function ($file) {
        return \Bnomei\Fingerprint::process($file)['hash'];
    },
    'integrity' => function ($file) {
        return \Bnomei\Fingerprint::process($file)['integrity'];
    }
  ]
]);
