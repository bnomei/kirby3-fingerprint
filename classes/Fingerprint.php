<?php

declare(strict_types=1);

namespace Bnomei;

use Exception;
use Kirby\Cms\File;
use Kirby\Cms\FileVersion;
use Kirby\Cms\Url;
use Kirby\Toolkit\A;

use function array_key_exists;

final class Fingerprint
{
    private array $options;

    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
            'query' => option('bnomei.fingerprint.query'),
            'hash' => option('bnomei.fingerprint.hash'),
            'integrity' => option('bnomei.fingerprint.integrity'),
            'digest' => option('bnomei.fingerprint.digest'),
            'https' => option('bnomei.fingerprint.https'),
            'ignore-missing-auto' => option('bnomei.fingerprint.ignore-missing-auto'),
            'absolute' => option('bnomei.fingerprint.absolute'),
        ];
        $this->options = array_merge($defaults, $options);

        foreach ($this->options as $key => $call) {
            if ($call instanceof \Closure && ! in_array($key, ['hash', 'integrity'])) {
                $this->options[$key] = $call();
            }
        }

        if ($this->option('debug')) {
            try {
                kirby()->cache('bnomei.fingerprint')->flush();
            } catch (Exception $e) {
                //
            }
        }
    }

    public function option(?string $key = null): mixed
    {
        if ($key) {
            return A::get($this->options, $key);
        }

        return $this->options;
    }

    public function apply(string $option, File|FileVersion|string $file): mixed
    {
        $callback = $this->option($option);

        if ($callback && is_callable($callback)) {
            if ($option === 'integrity') {
                return call_user_func_array($callback, [$file, $this->option('digest'), $this->option('query')]);
            } elseif ($option === 'hash') {
                return call_user_func_array($callback, [$file, $this->option('query')]);
            }
        }

        return null;
    }

    public function https(string $url): string
    {
        if ($this->option('https') && ! kirby()->system()->isLocal()) {
            $url = str_replace('http://', 'https://', $url);
        }

        if ($this->option('absolute') === false && kirby()->url() !== '/') { // in CLI
            $url = str_replace(rtrim(kirby()->url(), '/'), '', $url);
        }

        return $url;
    }

    public function process(File|FileVersion|string $file): array
    {
        $needsPush = false;
        $lookup = $this->read();
        if (! $lookup) {
            $lookup = [];
            $needsPush = true;
        }

        $finFile = new FingerprintFile($file);
        $id = $finFile->id();
        $mod = $finFile->modified();

        if (! array_key_exists($id, $lookup)) {
            $needsPush = true;
        } elseif ($mod && $lookup[$id]['modified'] < $mod) {
            $needsPush = true;
        }

        if ($needsPush) {
            $lookup[$id] = [
                'modified' => $mod,
                'root' => $finFile->fileRoot(),
                'integrity' => $this->apply('integrity', $file),
                'hash' => $this->apply('hash', $file),
            ];

            $this->write($lookup);
        }

        return A::get($lookup, $id);
    }

    public function attrs(array $attrs, array $lookup): array
    {
        $sri = A::get($attrs, 'integrity', false);
        if ($sri === true) {
            $sri = A::get($lookup, 'integrity');
        }
        if ($sri && strlen($sri) > 0) {
            $attrs['integrity'] = $sri;
            $attrs['crossorigin'] = A::get($attrs, 'crossorigin', 'anonymous');
        } elseif (! $sri) {
            if (array_key_exists('integrity', $attrs)) {
                unset($attrs['integrity']);
            }
            if (array_key_exists('crossorigin', $attrs)) {
                unset($attrs['crossorigin']);
            }
        }

        return $attrs;
    }

    public function helper(string $extension, File|FileVersion|string $url, array $attrs = []): ?string
    {
        if (! is_callable($extension)) {
            return null;
        }

        if ($url === '@auto') {
            $assetUrl = Url::toTemplateAsset($extension.'/templates', $extension);
            if ($assetUrl) {
                $url = $assetUrl;
            } elseif (! $assetUrl && $this->option('ignore-missing-auto')) {
                return null;
            }
        }

        $lookup = $this->process($url);
        $attrs = $this->attrs($attrs, $lookup);

        return $this->https($extension($lookup['hash'], $attrs));
    }

    public function cacheKey(): string
    {
        return implode('-', [
            'lookup',
            str_replace('.', '-', kirby()->plugin('bnomei/fingerprint')?->version() ?? '0.0.0'),
            $this->option('query') ? 'query' : 'redirect',
        ]);
    }

    public function read(): ?array
    {
        if ($this->option('debug')) {
            return null;
        }

        return kirby()->cache('bnomei.fingerprint')->get($this->cacheKey());
    }

    private function write(array $lookup): bool
    {
        if ($this->option('debug')) {
            return false;
        }

        return kirby()->cache('bnomei.fingerprint')->set($this->cacheKey(), $lookup);
    }

    public static function css(File|FileVersion|string $url, string|array $attrs = []): ?string
    {
        if (is_string($attrs)) {
            $attrs = ['media' => $attrs];
        }

        return (new Fingerprint)->helper('css', $url, $attrs);
    }

    public static function js(File|FileVersion|string $url, array $attrs = []): ?string
    {
        return (new Fingerprint)->helper('js', $url, $attrs);
    }

    public static function url(File|FileVersion|string $url): string
    {
        $fingerprint = new Fingerprint;
        $url = $fingerprint->process($url)['hash'];

        return $fingerprint->https($url);
    }
}
