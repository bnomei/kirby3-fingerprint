# Kirby 3 Fingerprint

![GitHub release](https://img.shields.io/github/release/bnomei/kirby3-fingerprint.svg?maxAge=1800) ![License](https://img.shields.io/github/license/mashape/apistatus.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-3%2B-black.svg)

File Method and css/js helper to add hash to files.

```php
echo Bnomei\Fingerprint::css('/assets/css/index.css');
echo Bnomei\Fingerprint::js('/assets/js/index.min.js');

// fileMethods not working (yet). issue pending.
echo $page->image('ukulele.jpg')->fingerprint();
```

## Settings

**hash**
_default: null_
which yields `function ($file) { return \filemtime($file); }`.

> NOTE: config settings do not override plugin settings (yet). issue pending.

## TODO

- hijack `css` and `js` and their auto variant helpers
- solve new folder setup complexity instead of just using index – which if fine for most public assets.

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-fingerprint/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.

## Credits

@iksi, https://github.com/iksi/kirby-fingerprint (Kirby V2)
