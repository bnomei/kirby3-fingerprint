<?php

namespace Bnomei;

class Fingerprint
{
    static public function css($url) {
        return \css(self::injectFileMTime($url)); // TODO:
    }

    static public function js($url) {
        return \js(self::injectFileMTime($url)); // TODO:
    }

    static private function isWebpack() {
        return !!(
            isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && $_SERVER['HTTP_X_FORWARDED_FOR'] == 'webpack'
        );
    }

    static private function isLocalhost() {
        return in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ));
    }

    static private function fingerprint($file) {
        $callback = option('bnomei.fingerprint.hash', null);

        if($callback && is_callable($callback)) {
            return call_user_func_array($callback, [$file]);
        }

        return \filemtime($file);
    }

    static public function injectFileMTime($url) {
        if(self::isWebpack() || self::isLocalhost()) return $url;

        $file = is_a($url, 'File') ? $file->root() : 
            \Kirby\CMS\App::instance()->roots()->index() . DIRECTORY_SEPARATOR . ltrim($url, '/');

        if(\F::exists($file)) {
            $filename = \F::name($file) . '.' . static::fingerprint($file) . '.' . \F::extension($file);
            $dirname = \dirname($file);
            $url = ($dirname === '.') ? $filename : ($dirname . '/' . $filename);
        }
        return $url;
    }
}
