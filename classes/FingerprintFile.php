<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cms\File;
use Kirby\Cms\FileVersion;
use Kirby\Data\Json;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Http\Url;
use Kirby\Toolkit\A;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Str;

use function dirname;
use function filemtime;
use function url;

final class FingerprintFile
{
    /**
     * Input file
     *
     * @var File|FileVersion|string
     */
    private $file;


    /**
     * Whether input file represents 'File' object
     *
     * @var bool
     */
    private $isKirbyFile;


    /**
     * FingerprintFile constructor.
     *
     * @param File|FileVersion|string $file Input file
     * @return void
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
     * Retrieves absolute path to input file
     *
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
     * Retrieves last modification time of input file
     *
     * @return int|null
     */
    public function modified(): ?int
    {
        $root = $this->fileRoot();
        if (!F::exists($root)) {
            return null;
        }

        $modified = null;
        if ($this->isKirbyFile && function_exists('modified') && $this->file->autoid()->isNotEmpty()) {
            // @codeCoverageIgnoreStart
            $modified = modified($this->file->autoid()->value());
            if (!$modified) {
                $modified = $this->file->modified();
            }
        // @codeCoverageIgnoreEnd
        } else {
            $modified = F::modified($root);
        }

        return $modified;
    }


    /**
     * Provides URL path for hashed filename
     *
     * @param bool|string $query Option determining use of query string vs file hash
     * @return string URL path pointing to (modified) file
     */
    public function hash($query = true): string
    {
        $root = $this->fileRoot();

        $filename = null;
        if (is_string($query) && F::exists($query)) {
            $manifest = Json::read($query);
            if (is_array($manifest)) {
                $url = '';
                if (kirby()->language()) {
                    $url = preg_replace('/\/'. kirby()->language()->code() .'$/', '', kirby()->site()->url());
                }
                $url = str_replace($url, '', $this->id());

                $hasLeadingSlash = Str::substr(array_keys($manifest)[0], 0, 1) === '/';
                $url = Url::path($url, $hasLeadingSlash);

                $filename = basename(A::get(
                    $manifest,
                    $url,
                    $root
                ));
            }
        } elseif (is_bool($query)) {
            if (!F::exists($root)) {
                return url($this->file);
            }

            # Determine file suffix, either ..
            $suffix = $query
                # .. query string
                ? F::extension($root) . '?v=' . filemtime($root)
                # .. MD5 filehash
                : md5_file($root) . '.' . F::extension($root);

            $filename = implode('.', [F::name($root), $suffix]);
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
     * Calculates SRI hash value for input file
     *
     * @param string|null $digest Cryptographic digest function
     * @param string|null $manifest Path to manifest file
     * @return string|null Subresource integrity string
     */
    public function integrity(?string $digest = null, ?string $manifest = null): ?string
    {
        $root = $this->fileRoot();

        if (is_string($manifest) && F::exists($manifest)) {
            $data = Json::read($manifest);

            if (is_array($data)) {
                $url = '';
                if (kirby()->language()) {
                    $url = preg_replace('/\/'. kirby()->language()->code() .'$/', '', kirby()->site()->url());
                }
                $url = str_replace($url, '', $this->id());
                $hasLeadingSlash = Str::substr(array_keys($data)[0], 0, 1) === '/';
                $url = Url::path($url, $hasLeadingSlash);

                $filename = basename(A::get($data, $url, $root));
                $dest = str_replace(basename($root), $filename, $root);

                if (F::exists($dest)) {
                    $root = $dest;
                }
            }
        }

        if (!F::exists($root)) {
            return null;
        }

        # Select hashing algorithm
        if (!in_array($digest, ['sha256', 'sha384', 'sha512'])) {
            $digest = 'sha384';
        }

        # If hashing file contents succeeds ..
        if ($hash = hash($digest, file_get_contents($root), true)) {
            # .. encode hash using 'base64'
            $b64 = base64_encode($hash);

            # Glue everything together, forming an SRI string
            return "{$digest}-{$b64}";
        }

        return null; // @codeCoverageIgnore
    }


    /**
     * Provides absolute path to input file
     *
     * @return string
     */
    public function fileRoot(): string
    {
        if ($this->isKirbyFile) {
            return $this->file->root();
        }
        $url = kirby()->site()->url();
        if (kirby()->language()) {
            $url = preg_replace('/\/'. kirby()->language()->code() .'$/', '', $url);
        }
        $path = ltrim($url, DIRECTORY_SEPARATOR);
        $uri = ltrim(str_replace($path, '', $this->file), DIRECTORY_SEPARATOR);
        return kirby()->roots()->index() . DIRECTORY_SEPARATOR . $uri;
    }


    /**
     * Provides input file
     *
     * @return File|FileVersion|string
     */
    public function file()
    {
        return $this->file;
    }
}
