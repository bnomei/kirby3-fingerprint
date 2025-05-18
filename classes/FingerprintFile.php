<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cms\File;
use Kirby\Cms\FileVersion;
use Kirby\Data\Json;
use Kirby\Filesystem\F;
use Kirby\Http\Url;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

use function dirname;
use function filemtime;
use function url;

final class FingerprintFile
{
    private string $file;

    private ?File $kirbyFile = null;

    public function __construct(File|FileVersion|string $file)
    {
        if ($file instanceof FileVersion) {
            /** @var File $o */
            $o = $file->original();
            $this->kirbyFile = $o;
            $this->file = $this->kirbyFile->url();
        } elseif ($file instanceof File) {
            $this->kirbyFile = $file;
            $this->file = $this->kirbyFile->url();
        } else {
            $this->file = url($file);
        }
    }

    public function id(): string
    {
        if ($this->kirbyFile) {
            return $this->kirbyFile->root() ?? '';
        }

        return ltrim($this->file, '/');
    }

    public function modified(): ?int
    {
        $root = $this->fileRoot();
        if (! F::exists($root)) {
            return null;
        }

        $modified = null;
        if ($this->kirbyFile && function_exists('modified')) {
            // @codeCoverageIgnoreStart
            $modified = \modified($this->kirbyFile);
            if (! $modified) {
                $modified = $this->kirbyFile->modified();
            }
            // @codeCoverageIgnoreEnd
        } else {
            $modified = F::modified($root);
        }

        return $modified;
    }

    public function hash(bool|string $query = true): string
    {
        $root = $this->fileRoot();

        $filename = null;
        if (is_string($query) && F::exists($query)) {
            $filename = $this->filenameFromQuery($query, $root);
        } elseif (is_bool($query)) {
            if (! F::exists($root)) {
                return $this->file;
            }

            // Determine file suffix, either ..
            $suffix = $query
                // ... query string
                ? F::extension($root).'?v='.filemtime($root)
                // ... MD5 file hash
                : md5_file($root).'.'.F::extension($root);

            $filename = implode('.', [F::name($root), $suffix]);
        }

        if (! $filename) {
            throw new \Exception("File not found: $root");
        }

        $url = null;
        if ($this->kirbyFile) {
            $url = str_replace($this->kirbyFile->filename(), $filename, $this->kirbyFile->url());
        } else {
            $dirname = str_replace(kirby()->roots()->index(), '', dirname($root));
            $url = ($dirname === '.') ? $filename : ($dirname.'/'.$filename);
        }

        return url($url);
    }

    public function integrity(?string $digest = null, ?string $manifest = null): ?string
    {
        $root = $this->fileRoot();

        if (is_string($manifest) && F::exists($manifest)) {
            $filename = $this->filenameFromQuery($manifest, $root);
            $dest = str_replace(basename($root), $filename, $root);

            if (F::exists($dest)) {
                $root = $dest;
            }
        }

        if (! F::exists($root)) {
            return null;
        }

        // Select hashing algorithm
        if (! $digest || ! in_array($digest, ['sha256', 'sha384', 'sha512'])) {
            $digest = 'sha384';
        }

        $data = F::read($root);
        if (! $data) {
            return null;
        }

        $hash = hash($digest, $data, true);
        // .. encode hash using 'base64'
        $b64 = base64_encode($hash);

        // Glue everything together, forming an SRI string
        return "{$digest}-{$b64}";
    }

    public function fileRoot(): string
    {
        if ($this->kirbyFile) {
            return $this->kirbyFile->root() ?? '';
        }

        $url = kirby()->site()->url();

        if ($lang = kirby()->language()) {
            $url = preg_replace('/\/'.$lang->code().'$/', '', $url);
        }

        if (! $url) {
            return '';
        }

        $path = ltrim($url, DIRECTORY_SEPARATOR);
        $uri = ltrim(str_replace($path, '', $this->file), DIRECTORY_SEPARATOR);

        return kirby()->roots()->index().DIRECTORY_SEPARATOR.$uri;
    }

    public function file(): File|string
    {
        return $this->kirbyFile ?? $this->file;
    }

    public function filenameFromQuery(string $query, string $root): string
    {
        $manifest = Json::read($query);
        $url = '';

        if (kirby()->language()) {
            $url = preg_replace('/\/'.kirby()->language()->code().'$/', '', kirby()->site()->url());
        }

        $url = str_replace($url ?? '', '', $this->id());

        $hasLeadingSlash = Str::substr(array_keys($manifest)[0], 0, 1) === '/';
        $url = Url::path($url, $hasLeadingSlash);

        return basename(A::get(
            $manifest,
            $url,
            $root
        ));
    }
}
