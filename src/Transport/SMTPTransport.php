<?php

declare(strict_types=1);

namespace PCore\Mailer\Transport;

use PCore\Mailer\Contracts\TransportInterface;

/**
 * Class SMTPTransport
 * @package PCore\Mailer\Transport
 * @github https://github.com/pcore-framework/mailer
 */
class SMTPTransport implements TransportInterface
{

    /**
     * @param $from string
     * @param $to mixed
     * @param $message string
     * @param null $headers
     * @return bool
     */
    public function send(string $from, mixed $to, string $message, $headers = null): bool
    {
        return true;
    }

}