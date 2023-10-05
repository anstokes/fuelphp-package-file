<?php

use Fuel\Core\Autoloader;

Autoloader::add_classes([
    'Anstech\File'          => __DIR__ . '/classes/file.php',
    'Anstech\UploadHandler' => __DIR__ . '/classes/uploadhandler.php',
]);
