<?php

require_once __DIR__.'/../vendor/autoload.php';

use Bnomei\Fingerprint;
use Bnomei\FingerprintFile;
use Kirby\Filesystem\F;

beforeEach(function () {
    kirby()->cache('bnomei.fingerprint')->flush();

    $this->testFile = page('home')->file('test.png');
    $this->assetPath = 'assets/asset.png';
    $this->invalidPath = 'invalid/file.jpg';
});
test('construct', function () {
    $fipr = new Fingerprint;
    expect($fipr)->toBeInstanceOf(Fingerprint::class);
});
test('options', function () {
    $fipr = new Fingerprint([
        'https' => false,
        'debug' => function () {
            return false;
        },
    ]);
    expect($fipr->option('https'))->toBeFalse()
        ->and($fipr->option('debug'))->toBeFalse()
        ->and($fipr->option())->toBeArray();
});
test('apply', function () {
    $fipr = new Fingerprint;
    expect($fipr->apply('invalid-apply-call', $this->testFile))->toBeNull()
        ->and($fipr->apply('hash', $this->testFile))->toMatch('/^.*\/test.png\?v=\d{10}$/');
});
test('https', function () {
    $fipr = new Fingerprint;
    expect($fipr->https('http://example.com'))->toMatch('/^https:/');

    $fipr = new Fingerprint([
        'https' => false,
    ]);
    $this->assertDoesNotMatchRegularExpression('/^https:/', $fipr->https('http://example.com'));
});
test('process', function () {
    $fipr = new Fingerprint;
    $lookup = $fipr->process($this->testFile);
    expect($lookup)->toBeArray();
    $lookup = $fipr->process($this->assetPath);
    expect($lookup)->toBeArray()
        ->and($lookup)->toHaveKey('modified')
        ->and($lookup)->toHaveKey('root')
        ->and($lookup)->toHaveKey('integrity')
        ->and($lookup)->toHaveKey('hash');

    $root = (new FingerprintFile($this->assetPath))->fileRoot();
    expect(F::exists($root))->toBeTrue();

    // this does not work on travis/gh ...
    // $this->assertTrue(touch($root, time()-24*60*7));
    // clearstatcache(); // https://stackoverflow.com/a/17380654
    // ... use copy/unlink instead
    copy($root, $root.'.bak');
    unlink($root);
    copy($root.'.bak', $root);
    unlink($root.'.bak');
    $lookupTouched = $fipr->process($this->assetPath);
    expect($lookup['modified'] !== $lookupTouched['modified'])->toBeTrue()
        ->and($lookup['hash'] !== $lookupTouched['hash'])->toBeTrue();
});
test('cache', function () {
    $fipr = new Fingerprint;
    $lookup = $fipr->process($this->testFile);
    expect($lookup)->toBeArray();

    // again to trigger cache lookup
    $lookup = $fipr->process($this->testFile);
    expect($fipr->read($this->testFile))->toBeArray()
        ->and($lookup)->toBeArray();

    $fipr = new Fingerprint([
        'debug' => true,
    ]);

    expect($fipr->read($this->testFile))->toBeNull();
    $lookup = $fipr->process($this->testFile);

    expect($fipr->read($this->testFile))->toBeNull()
        ->and($fipr->process('/assets/css/main.css')['hash'])->toMatch('/\/assets\/css\/main\.css\?v=\d{10}/')
        ->and($fipr->process('/assets/js/main.js')['hash'])->toMatch('/\/assets\/js\/main\.js\?v=\d{10}/');
});
test('attrs', function () {
    $fipr = new Fingerprint;
    $lookup = $fipr->process($this->testFile);

    $attrs = [];
    $attrs = $fipr->attrs($attrs, $lookup);
    expect($attrs)->toHaveCount(0);

    $attrs = [
        'integrity' => true,
    ];
    $attrs = $fipr->attrs($attrs, $lookup);
    expect($attrs)->toHaveCount(2)
        ->and($attrs)->toHaveKey('integrity')
        ->and($attrs)->toHaveKey('crossorigin');

    $attrs = [
        'integrity' => 'custom-sri',
    ];
    $attrs = $fipr->attrs($attrs, $lookup);
    expect($attrs)->toHaveCount(2)
        ->and($attrs)->toHaveKey('integrity')
        ->and($attrs['integrity'])->toEqual('custom-sri');

    $attrs = [
        'integrity' => false,
        'crossorigin' => 'anonymous',
    ];
    $attrs = $fipr->attrs($attrs, $lookup);
    expect($attrs)->toHaveCount(0);
});
test('helper', function () {
    $fipr = new Fingerprint;
    expect($fipr->helper('nope', '@auto'))->toBeNull();
});
test('static url', function () {
    expect(Fingerprint::url('assets/css/main.css'))->toMatch('/\/assets\/css\/main\.css\?v=\d{10}$/');
});
test('static css', function () {
    expect(Fingerprint::css('assets/test.css'))->toEqual('<link href="/assets/test.css" rel="stylesheet">')
        ->and(Fingerprint::css('assets/css/main.css'))->toMatch('/\/assets\/css\/main\.css\?v=\d{10}/')
        ->and(Fingerprint::css('/assets/css/main.css'))->toMatch('/\/assets\/css\/main\.css\?v=\d{10}/')
        ->and(Fingerprint::css('@auto'))->toMatch('/\/assets\/css\/templates\/default\.css\?v=\d{10}/');
});
test('static js', function () {
    expect(Fingerprint::js('assets/test.js'))->toEqual('<script src="/assets/test.js"></script>')
        ->and(Fingerprint::js('assets/js/main.js'))->toMatch('/\/assets\/js\/main\.js\?v=\d{10}/')
        ->and(Fingerprint::js('/assets/js/main.js'))->toMatch('/\/assets\/js\/main\.js\?v=\d{10}/')
        ->and(Fingerprint::js('@auto'))->toMatch('/\/assets\/js\/templates\/default\.js\?v=\d{10}/');
});
test('redirect rules instead of query', function () {
    $fipr = new Fingerprint([
        'query' => false,
    ]);

    expect($fipr->process('/assets/css/main.css')['hash'])->toMatch('/\/assets\/css\/main\.[a-z0-9]{32}\.css/')
        ->and($fipr->process('/assets/js/main.js')['hash'])->toMatch('/\/assets\/js\/main\.[a-z0-9]{32}\.js/');
});
test('manifest instead of query', function () {
    $fipr = new Fingerprint([
        'query' => function () {
            return __DIR__.'/manifest.json';
        },
    ]);

    expect($fipr->process('/assets/css/main.css')['hash'])->toMatch('/\/assets\/css\/main\.1234567890\.css/');
});
