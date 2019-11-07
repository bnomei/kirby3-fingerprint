<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Http\Url;
use Kirby\Toolkit\A;
use Kirby\Toolkit\F;
use function dirname;
use function filemtime;
use function url;

final class FingerprintFile
{
    /*
     * @var Kirby\Cms\File|Kirby\Cms\FileVersion
     */
    private $file;

    /*
     * @var bool
     */
    private $isKirbyFile;

    /**
     * FingerprintFile constructor.
     *
     * @param $file
     * @throws InvalidArgumentException
     */
    public function __construct($file)
    {
        $this->file = $file;
        $this->isKirbyFile = is_a($file, 'Kirby\Cms\File') || is_a($file, 'Kirby\Cms\FileVersion');
        if (!$this->isKirbyFile) {
            $this->file = url($this->file);
        }
    }

    /**
     * @return string
     */
    public function id(): string
    {
        if ($this->isKirbyFile) {
            return $this->file->root();
        }

        return ltrim($this->file, '/');
    }

    /**
     * @return int|null
     */
    public function modified(): ?int
    {
        $root = $this->fileRoot();
        if (! F::exists($root)) {
            return null;
        }

        if ($this->isKirbyFile && function_exists('autoid') && $this->file->autoid()->isNotEmpty()) {
            // @codeCoverageIgnoreStart
            $autoid = autoid()->filterBy('autoid', $this->file->autoid())->first();
            return $autoid && is_array($autoid) ? A::get($autoid, 'modified') : F::modified($root);
            // @codeCoverageIgnoreEnd
        }

        return F::modified($root);
    }

    /**
     * @param $query
     * @return string
     */
    public function hash($query = true): string
    {
        $root = $this->fileRoot();

        if (! F::exists($root)) {
            return url($this->file);
        }


        $filename = null;
        if (is_string($query) && F::exists($query)) {
            $manifest = json_decode(F::read($query), true);
            if (is_array($manifest)) {
                $filename = basename(A::get(
                    $manifest,
                    $this->id(),
                    $root
                ));
            }
        } elseif (is_bool($query)) {
            $filename = implode('.', [
                F::name($root),
                $query ? F::extension($root) . '?v=' . filemtime($root) : md5_file($root) . '.' . F::extension($root)
            ]);
        }

        $url = null;
        if ($this->isKirbyFile) {
            $url = str_replace($this->file->filename(), $filename, $this->file->url());
        } else {
            $dirname = str_replace(kirby()->roots()->index(), '', dirname($root));
            $url = ($dirname === '.') ? $filename : ($dirname . '/' . $filename);
        }

        return url($url);
    }

    /**
     * @param bool $openssl
     * @return string|null
     */
    public function integrity(bool $openssl = true): ?string
    {
        $root = $this->fileRoot();

        if (! F::exists($root)) {
            return null;
        }

        if ($openssl && extension_loaded('openssl')) {
            // https://www.srihash.org/
            exec('openssl dgst -sha384 -binary ' . $root . ' | openssl base64 -A', $output, $return);
            if (is_array($output) && count($output) >= 1) {
                return 'sha384-' . $output[0];
            }
        }

        exec('shasum -b -a 384 ' . $root . ' | xxd -r -p | base64', $output, $return);
        if (is_array($output) && count($output) >= 1) {
            return 'sha384-' . $output[0];
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * @return string
     */
    public function fileRoot(): string
    {
        if ($this->isKirbyFile) {
            return $this->file->root();
        }
        $uri = ltrim(
            str_replace(kirby()->site()->url(), '', $this->file),
            DIRECTORY_SEPARATOR
        );
        return kirby()->roots()->index() . DIRECTORY_SEPARATOR . $uri;
    }

    /**
     * @return mixed
     */
    public function file()
    {
        return $this->file;
    }
}
