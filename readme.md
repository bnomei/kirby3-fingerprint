# Kirby 3 Fingerprint

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-fingerprint?color=ae81ff)
![Stars](https://flat.badgen.net/packagist/ghs/bnomei/kirby3-fingerprint?color=272822)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-fingerprint?color=272822)
![Issues](https://flat.badgen.net/packagist/ghi/bnomei/kirby3-fingerprint?color=e6db74)
[![Build Status](https://flat.badgen.net/travis/bnomei/kirby3-fingerprint)](https://travis-ci.com/bnomei/kirby3-fingerprint)
[![Coverage Status](https://flat.badgen.net/coveralls/c/github/bnomei/kirby3-fingerprint)](https://coveralls.io/github/bnomei/kirby3-fingerprint) 
[![Demo](https://flat.badgen.net/badge/website/examples?color=f92672)](https://kirby3-plugins.bnomei.com/fingerprint) 
[![Gitter](https://flat.badgen.net/badge/gitter/chat?color=982ab3)](https://gitter.im/bnomei-kirby-3-plugins/community) 
[![Twitter](https://flat.badgen.net/badge/twitter/bnomei?color=66d9ef)](https://twitter.com/bnomei)


File Method and css/js helper to add cachbusting hash and optional [Subresource Integrity](https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity) to files.

## Commerical Usage

This plugin is free but if you use it in a commercial project please consider to 
- [make a donation 🍻](https://www.paypal.me/bnomei/4) or
- [buy me ☕](https://buymeacoff.ee/bnomei) or
- [buy a Kirby license using this affiliate link](https://a.paddle.com/v2/click/1129/35731?link=1170)

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-fingerprint/archive/master.zip) as folder `site/plugins/kirby3-fingerprint` or
- `git submodule add https://github.com/bnomei/kirby3-fingerprint.git site/plugins/kirby3-fingerprint` or
- `composer require bnomei/kirby3-fingerprint`

## Performance

Hash and SRI values are cached and only updated when original file is modified.

## Usage

```php
echo Bnomei\Fingerprint::css('/assets/css/index.css');
// https://../assets/css/index.css?v=1203291283

echo Bnomei\Fingerprint::js('/assets/js/index.min.js');
// https://../assets/js/index.min.js?v=1203291283

echo $page->file('ukulele.pdf')->fingerprint();
// https://../ukulele.pdf?v=1203291283

echo $page->file('ukulele.pdf')->integrity();
// sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC

// generate sri from local file
echo Bnomei\Fingerprint::js(
    '/assets/js/index.min.js', 
    [
        "integrity" => true
    ]
); 
/*
<script src="https://../assets/js/index.min.js"
    integrity="sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC"
    crossorigin="anonymous"></script>
*/

echo Bnomei\Fingerprint::js(
    'https://external.cdn/framework.min.js', 
    [
        "integrity" => "sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC"
    ]
);
/*
<script src="https://external.cdn/framework.min.js"
    integrity="sha384-oqVuAfXRKap7fdgcCY5uykM6+R9GqQ8K/uxy9rx7HNQlGYl1kPzQho1wx4JwY8wC"
    crossorigin="anonymous"></script>
*/
```

## Settings

**debugforce**
- default: `true` will flush cache if debug mode is active

**hash**
- default: will lead to query string and does not require htaccess setup. thanks @fabianmichael. [#1](https://github.com/bnomei/kirby3-fingerprint/issues/1)

**integrity**
- to disable sri set option `'integrity' => null,`

**ssl**
- default: `false`. boolean value or callback to force *https* scheme.

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-fingerprint/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

## Similar Plugins

- consider [kirby-fingerprint](https://github.com/bvdputte/kirby-fingerprint) if you do not want a querystring (`myfile.js?v=1928356`) but an filename rewrite rule using htaccess (`myfile.1928356.js`) and you do not want to override the `bnomei.fingerprint.hash` option like [described here](https://github.com/bnomei/kirby3-fingerprint/issues/1#issuecomment-470867793).

## Credits

- based on [@iksi](https://github.com/iksi) https://github.com/iksi/kirby-fingerprint (Kirby V2)
- [@S1SYPHOS](https://github.com/S1SYPHOS) https://github.com/S1SYPHOS/kirby-sri (Kirby V2)
