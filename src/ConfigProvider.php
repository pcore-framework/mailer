<?php

declare(strict_types=1);

namespace PCore\View;

/**
 * Class ConfigProvider
 * @package PCore\View
 * @github https://github.com/pcore-framework/mailer
 */
class ConfigProvider
{

    public function __invoke(): array
    {
        return [
            'publish' => [
                [
                    'name' => 'view',
                    'source' => __DIR__ . '/../publish/mailer.php',
                    'destination' => dirname(__DIR__, 4) . '/config/mailer.php'
                ]
            ]
        ];
    }

}