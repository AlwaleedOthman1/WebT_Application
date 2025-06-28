<?php

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $settings = require __DIR__ . '/settings.php';
    $settings($containerBuilder);

    // Database connection
    $database = require __DIR__ . '/database.php';
    $database($containerBuilder);
}; 