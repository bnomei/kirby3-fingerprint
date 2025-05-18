# Kirby Fingerprint

[![Kirby 5](https://flat.badgen.net/badge/Kirby/5?color=ECC748)](https://getkirby.com)
![PHP 8.2](https://flat.badgen.net/badge/PHP/8.2?color=4E5B93&icon=php&label)
![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-fingerprint?color=ae81ff&icon=github&label)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-fingerprint?color=272822&icon=github&label)
[![Coverage](https://flat.badgen.net/codeclimate/coverage/bnomei/kirby3-fingerprint?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby3-fingerprint)
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby3-fingerprint?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby3-fingerprint/issues)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)


File Method and css/js helper to add a cache busting hash and optional [Subresource Integrity](https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity) to files.

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-fingerprint/archive/master.zip) as folder `site/plugins/kirby3-fingerprint` or
- `git submodule add https://github.com/bnomei/kirby3-fingerprint.git site/plugins/kirby3-fingerprint` or
- `composer require bnomei/kirby3-fingerprint`

## Usage

> [!WARNING]
> This Plugin does **not** override the build in `js()`/`css()` helpers. Use `css_f`/`Bnomei\Fingerprint::css` and `js_f`/`Bnomei\Fingerprint::js` when you need them.

```php
echo css_f('/assets/css/index.css');
echo Bnomei\Fingerprint::css('/assets/css/index.css');
// <style> element with https://../assets/css/index.css?v=1203291283

echo js_f('/assets/js/index.min.js');
echo Bnomei\Fingerprint::js('/assets/js/index.min.js');
// <link> element https://../assets/js/index.min.js?v=1203291283

echo url_f('/assets/css/index.css');
echo Bnomei\Fingerprint::url('/assets/css/index.css');
// raw url https://../assets/css/index.css?v=1203291283

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

## Cache

> [!WARNING]
> If **global** debug mode is `true,` the plugin will flush its cache and not write any more caches.

Hash and SRI values are cached and only updated when the original file is modified.

For best performance, set either the [global or plugin-specific cache driver](https://getkirby.com/docs/reference/system/options/cache) to one using the server's memory, not the default using files on the hard disk (even on SSDs). If available, I suggest Redis/APCu or leave it at `file` otherwise.

**site/config/config.php**
```php
return [
  'cache' => [
    'driver' => 'apcu', // or redis
  ],
  'bnomei.fingerprint.cache' => [
    'type' => 'apcu', // or redis
  ],
];
```

## Similar Plugins

The following plugins can do cache busting, but they do not cache the modified timestamp, nor can they do SRI, nor do cache busting for non-js/CSS files.

- [bvdputte/kirby-fingerprint](https://github.com/bvdputte/kirby-fingerprint)
- [schnti/kirby3-cachebuster](https://github.com/schnti/kirby3-cachebuster)
- [diverently/laravel-mix-kirby](https://github.com/diverently/laravel-mix-kirby)

## Settings

| bnomei.fingerprint. | Default                          | Description                                                                               |
|---------------------|----------------------------------|-------------------------------------------------------------------------------------------|
| hash                | `callback`                       | will lead to the hashing logic                                                            |
| integrity           | `callback`                       | use it to set option `'integrity' => null,`                                               |
| digest              | `'sha384'`                       | Cryptographic digest to be used for SRI hashes either `'sha256'`, `'sha384'` or `'sha512'`. |
| https               | `true`                           | boolean value or callback to force *https* scheme on all but localhost enviroments.       |
| query               | `true` or `string` or `callback` | `myfile.js?v={HASH}`, `myfile.{HASH}.js` or loaded from manifest file                     |
| ignore-missing-auto | `true` or `false`                | silently ignore if an asset requested by @auto rule does not exist                        |
| absolute            | `true` or `false`                | return the full URL of an asset or relative URLS based on site()->url()                   |


### Query option: true (default)

```
myfile.js?v={HASH}
```

This is the default since it works without additional changes to your server but be aware that [query strings are not perfect](http://www.stevesouders.com/blog/2008/08/23/revving-filenames-dont-use-querystring/).

### Query option: false

If you disable the query option, you also need to add Apache or Nginx rules. These rules will redirect CSS and JS files from with hash to the asset on disk.

**.htaccess** - put this directly after the `RewriteBase` statment
```apacheconfig
# RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)\.([0-9a-z]{32})\.(js|css)$ $1.$3 [L]
```

**Nginx virtual host setup**
```
location ~ (.+)\.(?:\w+)\.(js|css)$ {
    try_files $uri $1.$2;
}
```

### Query option: string (Manifest files)

You can also forward the path of a JSON-encoded manifest file, and the plugin will load whatever hash is defined there. This works great for [gulp-rev](https://github.com/sindresorhus/gulp-rev) or with [laravel mix versioning](https://laravel-mix.com/docs/master/versioning).


## Disclaimer

This plugin is provided "as is" with no guarantee. You can use it at your own risk and always test it before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-fingerprint/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

## Credits

- based on [@iksi](https://github.com/iksi) https://github.com/iksi/kirby-fingerprint (Kirby V2)
- [@S1SYPHOS](https://github.com/S1SYPHOS) https://github.com/S1SYPHOS/kirby-sri (Kirby V2)
