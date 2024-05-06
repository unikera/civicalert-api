<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            // Utilisez le nom du service Docker pour le service MariaDB comme hôte
            $dbHost = getenv('DB_HOST') ?: 'civicalertapi-mariadb-1';  // Default to 'mariadb' if not set in env
            $dbPort = getenv('DB_PORT') ?: 3306;       // Default to '3307' if not set in env
            $dbUser = getenv('DB_USER') ?: 'user';     // Default to 'user' if not set in env
            $dbPassword = getenv('DB_PASSWORD') ?: 'password'; // Default to 'password' if not set in env
            $dbName = getenv('DB_NAME') ?: 'civicalert';       // Default to 'civicalert' if not set in env

            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],

                'db' => [
                    'driver' => 'mysql',
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'username' => $dbUser,
                    'password' => $dbPassword,
                    'database' => $dbName,
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'flags' => [
                        PDO::ATTR_PERSISTENT => false,
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => true,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8 COLLATE utf8_unicode_ci'
                    ],
                ],
            ]);
        }
    ]);
};
