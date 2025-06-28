<?php

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        'settings' => [
            'displayErrorDetails' => true, // Should be false in production
            'renderer' => [
                'template_path' => __DIR__ . '/../templates/',
            ],
            'logger' => [
                'name' => 'slim-app',
                'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                'level' => \Monolog\Logger::DEBUG,
            ],
            // Database connection settings
            'db' => [
                'host' => 'localhost',
                'dbname' => 'webt_application',
                'user' => 'root',
                'pass' => 'root',
                'charset' => 'utf8mb4',
            ],
        ],
    ]);
}; 