<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Bnomei\FingerprintFile;
use PHPUnit\Framework\TestCase;

class FingerprintFileTest extends TestCase
{
    /*
     * @var Kirby\Cms\File|Kirby\Cms\FileVersion
     */
    private $testFile;

    /*
     * @var string
     */
    private $assetPath;

    /*
     * @var string
     */
    private $invalidPath;

    /*
     * @var string
     */
    private $url;

    public function setUp(): void
    {
        $this->testFile = page('home')->file('test.png');
        $this->assetPath = 'assets/asset.png';
        $this->invalidPath = 'invalid/file.jpg';
        $this->url = 'http://example.com/app.css';
    }

    public function testConstruct()
    {
        $file = new FingerprintFile($this->testFile);
        $this->assertInstanceOf(FingerprintFile::class, $file);

        $file = new FingerprintFile($this->assetPath);
        $this->assertInstanceOf(FingerprintFile::class, $file);
    }

    public function testId()
    {
        $file = new FingerprintFile($this->testFile);
        $this->assertEquals($this->testFile->root(), $file->id());

        $file = new FingerprintFile($this->assetPath);
        $this->assertEquals($this->assetPath, $file->id());

        $file = new FingerprintFile($this->url);
        $this->assertEquals($this->url, $file->id());
    }

    public function testModified()
    {
        $file = new FingerprintFile($this->testFile);
        $this->assertIsInt($file->modified());

        $file = new FingerprintFile($this->assetPath);
        $this->assertIsInt($file->modified());

        $file = new FingerprintFile($this->url);
        $this->assertNull($file->modified());
    }

    public function testFile()
    {
        $file = new FingerprintFile($this->testFile);
        $this->assertEquals($this->testFile, $file->file());

        $file = new FingerprintFile($this->assetPath);
        $this->assertEquals(url($this->assetPath), $file->file());

        $file = new FingerprintFile($this->invalidPath);
        $this->assertEquals(url($this->invalidPath), $file->file());
    }

    public function testFileroot()
    {
        $file = new FingerprintFile($this->testFile);
        $this->assertEquals(
            $this->testFile->root(),
            $file->fileRoot()
        );

        $file = new FingerprintFile($this->assetPath);
        $this->assertEquals(
            kirby()->roots()->index() . DIRECTORY_SEPARATOR . $this->assetPath,
            $file->fileRoot()
        );

        $file = new FingerprintFile(DIRECTORY_SEPARATOR . $this->assetPath);
        $this->assertEquals(
            kirby()->roots()->index() . DIRECTORY_SEPARATOR . $this->assetPath,
            $file->fileRoot()
        );

        $file = new FingerprintFile($this->invalidPath);
        $this->assertEquals(
            kirby()->roots()->index() . DIRECTORY_SEPARATOR . $this->invalidPath,
            $file->fileRoot()
        );
    }

    public function testHash()
    {
        $file = new FingerprintFile($this->testFile);
        $this->assertMatchesRegularExpression('/^.*\/test\.png\?v=\d{10}$/', $file->hash());

        $file = new FingerprintFile($this->assetPath);
        $this->assertMatchesRegularExpression('/^\/assets\/asset\.png\?v=\d{10}$/', $file->hash());

        $file = new FingerprintFile($this->assetPath);
        $this->assertMatchesRegularExpression('/^\/assets\/asset\.\w{32}\.png$/', $file->hash(false));

        $file = new FingerprintFile($this->invalidPath);
        $this->assertEquals($file->file(), $file->hash());

        $file = new FingerprintFile('assets/css/main.css');
        $this->assertMatchesRegularExpression('/^.*\/main\.css\?v=\d{10}$/', $file->hash());

        $file = new FingerprintFile('assets/js/main.js');
        $this->assertMatchesRegularExpression('/^.*\/main\.js\?v=\d{10}$/', $file->hash());

        $file = new FingerprintFile('assets/css/main.css');
        $manifest = __DIR__ . '/manifest.json';
        $this->assertMatchesRegularExpression('/^.*assets\/css\/main\.\d{10}\.css$/', $file->hash($manifest));

        $file = new FingerprintFile('assets/manifest/invalid.png');
        $manifest = __DIR__ . '/manifest_renamed_src.json';
        $this->assertMatchesRegularExpression('/^.*assets\/manifest\/valid\.\d{10}\.png$/', $file->hash($manifest));
    }

    public function testIntegrity()
    {
        $file = new FingerprintFile($this->testFile);
        $this->assertMatchesRegularExpression('/^sha384-.{64}$/', $file->integrity());

        $file = new FingerprintFile($this->testFile);
        $this->assertMatchesRegularExpression('/^sha256-.{44}$/', $file->integrity('sha256'));

        $file = new FingerprintFile($this->testFile);
        $this->assertMatchesRegularExpression('/^sha384-.{64}$/', $file->integrity('sha384'));

        $file = new FingerprintFile($this->testFile);
        $this->assertMatchesRegularExpression('/^sha512-.{88}$/', $file->integrity('sha512'));

        $file = new FingerprintFile($this->assetPath);
        $this->assertMatchesRegularExpression('/^sha384-.{64}$/', $file->integrity());

        $file = new FingerprintFile($this->assetPath);
        $this->assertMatchesRegularExpression('/^sha256-.{44}$/', $file->integrity('sha256'));

        $file = new FingerprintFile($this->assetPath);
        $this->assertMatchesRegularExpression('/^sha384-.{64}$/', $file->integrity('sha384'));

        $file = new FingerprintFile($this->assetPath);
        $this->assertMatchesRegularExpression('/^sha512-.{88}$/', $file->integrity('sha512'));

        $file = new FingerprintFile($this->invalidPath);
        $this->assertNull($file->integrity());

        $file = new FingerprintFile('assets/css/main.css');
        $manifest = __DIR__ . '/manifest.json';
        $this->assertMatchesRegularExpression('/^sha384-.{64}$/', $file->integrity('sha384', $manifest));

        $file = new FingerprintFile('assets/manifest/invalid.png');
        $manifest = __DIR__ . '/manifest_renamed_src.json';
        $this->assertMatchesRegularExpression('/^sha384-.{64}$/', $file->integrity('sha384', $manifest));
    }
}
