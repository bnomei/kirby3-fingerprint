<?php

declare(strict_types=1);

namespace Bnomei;

use Exception;
use Kirby\Cms\File;
use Kirby\Cms\FileVersion;
use Kirby\Cms\Url;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\A;

use function array_key_exists;

final class Fingerprint
{
    /**
     * Plugin options
     *
     * @var array
     */
    private $options;


    /**
     * Fingerprint constructor.
     *
     * @param array $options Plugin options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'debug' => option('debug'),
            'query' => option('bnomei.fingerprint.query'),
            'hash' => option('bnomei.fingerprint.hash'),
            'integrity' => option('bnomei.fingerprint.integrity'),
            'digest' => option('bnomei.fingerprint.digest'),
            'https' => option('bnomei.fingerprint.https'),
        ];
        $this->options = array_merge($defaults, $options);

        foreach ($this->options as $key => $call) {
            if (is_callable($call) && !in_array($key, ['hash', 'integrity'])) {
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


    /**
     * Gets plugin option by its name
     *
     * @param string|null $key Option name
     * @return mixed
     */
    public function option(?string $key = null)
    {
        if ($key) {
            return A::get($this->options, $key);
        }
        return $this->options;
    }


    /**
     * Applies hash/integrity functions
     *
     * @param string $option Plugin option/function name
     * @param $file File|FileVersion|string Input file
     * @return mixed
     */
    public function apply(string $option, $file)
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

    /**
     * Formats URL to use 'https'
     *
     * @param string $url URL part
     * @return string
     */
    public function https(string $url): string
    {
        if ($this->option('https') && !kirby()->system()->isLocal()) {
            $url = str_replace('http://', 'https://', $url);
        }
        return $url;
    }

    /**
     * Processes input file
     *
     * @param File|FileVersion|string $file Input file
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function process($file)
    {
        $needsPush = false;
        $lookup = $this->read();
        if (!$lookup) {
            $lookup = [];
            $needsPush = true;
        }

        $finFile = new FingerprintFile($file);
        $id = $finFile->id();
        $mod = $finFile->modified();

        if (!array_key_exists($id, $lookup)) {
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

    /**
     * @param array $attrs
     * @param array $lookup
     * @return array
     */
    public function attrs(array $attrs, array $lookup)
    {
        $sri = A::get($attrs, 'integrity', false);
        if ($sri === true) {
            $sri = A::get($lookup, 'integrity');
        }
        if ($sri && strlen($sri) > 0) {
            $attrs['integrity'] = $sri;
            $attrs['crossorigin'] = A::get($attrs, 'crossorigin', 'anonymous');
        } elseif (!$sri) {
            if (array_key_exists('integrity', $attrs)) {
                unset($attrs['integrity']);
            }
            if (array_key_exists('crossorigin', $attrs)) {
                unset($attrs['crossorigin']);
            }
        }
        return $attrs;
    }

    /**
     * Helper shorthand
     *
     * @param string $extension Helper name
     * @param string $url URL part
     * @param array $attrs HTML attributes
     * @return string|null
     */
    public function helper(string $extension, string $url, array $attrs = []): ?string
    {
        if (!is_callable($extension)) {
            return null;
        }

        if ($url === '@auto') {
            $assetUrl = Url::toTemplateAsset($extension.'/templates', $extension);
            if ($assetUrl) {
                $url = $assetUrl;
            }
        }

        $lookup = $this->process($url);
        $attrs = $this->attrs($attrs, $lookup);

        return $this->https($extension($lookup['hash'], $attrs));
    }

    /**
     * Retrieves cache key
     *
     * @return string
     */
    public function cacheKey(): string
    {
        return implode('-', [
            'lookup',
            str_replace('.', '-', kirby()->plugin('bnomei/fingerprint')->version()),
            $this->option('query') ? 'query' : 'redirect'
        ]);
    }

    /**
     * Retrieves cached data
     *
     * @return array|null
     */
    public function read(): ?array
    {
        if ($this->option('debug')) {
            return null;
        }
        return kirby()->cache('bnomei.fingerprint')->get($this->cacheKey());
    }

    /**
     * Stores data in cache
     *
     * @param array $lookup
     * @return bool
     */
    private function write(array $lookup): bool
    {
        if ($this->option('debug')) {
            return false;
        }
        return kirby()->cache('bnomei.fingerprint')->set($this->cacheKey(), $lookup);
    }

    /**
     * Stylesheet helper
     *
     * @param $url URL part
     * @param array $attrs
     * @return string
     */
    public static function css($url, $attrs = []): string
    {
        if (is_string($attrs)) {
            $attrs = ['media' => $attrs];
        }

        return (new Fingerprint())->helper('css', $url, $attrs);
    }

    /**
     * JavaScript helper
     *
     * @param $url URL part
     * @param array $attrs HTML attributes
     * @return string
     */
    public static function js($url, $attrs = []): string
    {
        return (new Fingerprint())->helper('js', $url, $attrs);
    }

    /**
     * Builds full URL
     *
     * @param $url URL part
     * @param array $attrs HTML attributes
     * @return string
     */
    public static function url($url): string
    {
        $fingerprint = new Fingerprint();
        $url = $fingerprint->process($url)['hash'];
        return $fingerprint->https($url);
    }
}
