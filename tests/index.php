<?php

require_once __DIR__.'/../vendor/autoload.php';

$kirby = new Kirby(['roots' => ['index' => __DIR__]]);

if (PHP_SAPI !== 'cli') {
    echo $kirby->render();
}
