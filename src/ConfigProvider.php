<?php

declare(strict_types=1);

namespace PCore\Mailer;

/**
 * Class ConfigProvider
 * @package PCore\Mailer
 * @github https://github.com/pcore-framework/mailer
 */
class ConfigProvider
{

    public function __invoke(): array
    {
        return [
            'publish' => [
                [
                    'name' => 'mailer',
                    'source' => __DIR__ . '/../publish/mailer.php',
                    'destination' => dirname(__DIR__, 4) . '/config/mailer.php'
                ]
            ]
        ];
    }

}
