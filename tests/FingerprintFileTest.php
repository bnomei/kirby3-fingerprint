<?php

require_once __DIR__.'/../vendor/autoload.php';

use Bnomei\FingerprintFile;

beforeEach(function () {
    $this->testFile = page('home')->file('test.png');
    $this->assetPath = 'assets/asset.png';
    $this->invalidPath = 'invalid/file.jpg';
    $this->url = 'http://example.com/app.css';
});
test('construct', function () {
    $file = new FingerprintFile($this->testFile);
    expect($file)->toBeInstanceOf(FingerprintFile::class);

    $file = new FingerprintFile($this->assetPath);
    expect($file)->toBeInstanceOf(FingerprintFile::class);
});
test('id', function () {
    $file = new FingerprintFile($this->testFile);
    expect($file->id())->toEqual($this->testFile->root());

    $file = new FingerprintFile($this->assetPath);
    expect($file->id())->toEqual($this->assetPath);

    $file = new FingerprintFile($this->url);
    expect($file->id())->toEqual($this->url);
});
test('modified', function () {
    $file = new FingerprintFile($this->testFile);
    expect($file->modified())->toBeInt();

    $file = new FingerprintFile($this->assetPath);
    expect($file->modified())->toBeInt();

    $file = new FingerprintFile($this->url);
    expect($file->modified())->toBeNull();
});
test('file', function () {
    $file = new FingerprintFile($this->testFile);
    expect($file->file())->toEqual($this->testFile);

    $file = new FingerprintFile($this->assetPath);
    expect($file->file())->toEqual(url($this->assetPath));

    $file = new FingerprintFile($this->invalidPath);
    expect($file->file())->toEqual(url($this->invalidPath));
});
test('fileroot', function () {
    $file = new FingerprintFile($this->testFile);
    expect($file->fileRoot())->toEqual($this->testFile->root());

    $file = new FingerprintFile($this->assetPath);
    expect($file->fileRoot())->toEqual(kirby()->roots()->index().DIRECTORY_SEPARATOR.$this->assetPath);

    $file = new FingerprintFile(DIRECTORY_SEPARATOR.$this->assetPath);
    expect($file->fileRoot())->toEqual(kirby()->roots()->index().DIRECTORY_SEPARATOR.$this->assetPath);

    $file = new FingerprintFile($this->invalidPath);
    expect($file->fileRoot())->toEqual(kirby()->roots()->index().DIRECTORY_SEPARATOR.$this->invalidPath);
});
test('hash', function () {
    $file = new FingerprintFile($this->testFile);
    expect($file->hash())->toMatch('/^.*\/test\.png\?v=\d{10}$/');

    $file = new FingerprintFile($this->assetPath);
    expect($file->hash())->toMatch('/^\/assets\/asset\.png\?v=\d{10}$/');

    $file = new FingerprintFile($this->assetPath);
    expect($file->hash(false))->toMatch('/^\/assets\/asset\.\w{32}\.png$/');

    $file = new FingerprintFile($this->invalidPath);
    expect($file->hash())->toEqual($file->file());

    $file = new FingerprintFile('assets/css/main.css');
    expect($file->hash())->toMatch('/^.*\/main\.css\?v=\d{10}$/');

    $file = new FingerprintFile('assets/js/main.js');
    expect($file->hash())->toMatch('/^.*\/main\.js\?v=\d{10}$/');

    $file = new FingerprintFile('assets/css/main.css');
    $manifest = __DIR__.'/manifest.json';
    expect($file->hash($manifest))->toMatch('/^.*assets\/css\/main\.\d{10}\.css$/');

    $file = new FingerprintFile('assets/manifest/invalid.png');
    $manifest = __DIR__.'/manifest_renamed_src.json';
    expect($file->hash($manifest))->toMatch('/^.*assets\/manifest\/valid\.\d{10}\.png$/');
});
test('integrity', function () {
    $file = new FingerprintFile($this->testFile);
    expect($file->integrity())->toMatch('/^sha384-.{64}$/');

    $file = new FingerprintFile($this->testFile);
    expect($file->integrity('sha256'))->toMatch('/^sha256-.{44}$/');

    $file = new FingerprintFile($this->testFile);
    expect($file->integrity('sha384'))->toMatch('/^sha384-.{64}$/');

    $file = new FingerprintFile($this->testFile);
    expect($file->integrity('sha512'))->toMatch('/^sha512-.{88}$/');

    $file = new FingerprintFile($this->assetPath);
    expect($file->integrity())->toMatch('/^sha384-.{64}$/');

    $file = new FingerprintFile($this->assetPath);
    expect($file->integrity('sha256'))->toMatch('/^sha256-.{44}$/');

    $file = new FingerprintFile($this->assetPath);
    expect($file->integrity('sha384'))->toMatch('/^sha384-.{64}$/');

    $file = new FingerprintFile($this->assetPath);
    expect($file->integrity('sha512'))->toMatch('/^sha512-.{88}$/');

    $file = new FingerprintFile($this->invalidPath);
    expect($file->integrity())->toBeNull();

    $file = new FingerprintFile('assets/css/main.css');
    $manifest = __DIR__.'/manifest.json';
    expect($file->integrity('sha384', $manifest))->toMatch('/^sha384-.{64}$/');

    $file = new FingerprintFile('assets/manifest/invalid.png');
    $manifest = __DIR__.'/manifest_renamed_src.json';
    expect($file->integrity('sha384', $manifest))->toMatch('/^sha384-.{64}$/');
});
