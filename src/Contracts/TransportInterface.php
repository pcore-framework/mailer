<?php

declare(strict_types=1);

namespace PCore\Mailer\Contracts;

/**
 * Interface TransportInterface
 * @package PCore\Mailer\Contracts
 * @github https://github.com/pcore-framework/mailer
 */
interface TransportInterface
{

    /**
     * @param string $from
     * @param mixed $to
     * @param string $message
     * @param null $headers
     * @return bool
     */
    public function send(string $from, mixed $to, string $message, $headers = null): bool;

}