<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fingerprint test site</title>
    <?= css_f('assets/css/main.css', ['integrity' => true]) ?>
</head>
<body>
    <h1>Fingerprint test site</h1>
    <?php if ($file = $page->file('test.png')) { ?>
        <a href="<?= esc($file->fingerprint(), 'attr') ?>">Open <?= esc($file->filename()) ?></a>
        <p>File integrity: <code><?= esc($file->integrity()) ?></code></p>
    <?php } ?>
    <?= js_f('assets/js/main.js', ['integrity' => true]) ?>
</body>
</html>
