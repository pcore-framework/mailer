<?php

declare(strict_types=1);

namespace PCore\Mailer\Transport;

use PCore\Mailer\Contracts\TransportInterface;
use PCore\Mailer\Exceptions\Exception;

/**
 * Class SMTPTransport
 * @package PCore\Mailer\Transport
 * @github https://github.com/pcore-framework/mailer
 */
class SMTPTransport implements TransportInterface
{

    /**
     * smtp транспортный параметр
     *
     * @var array
     */
    public array $transport = [];

    /**
     * Режим отладки
     *
     * @var boolean|integer
     */
    public bool|int $debugMode = false;

    /**
     * Разрешенные шифрования
     *
     * @var array
     */
    private array $allowedEncryptions = ['ssl', 'tls'];

    public function __construct()
    {
        $this->transport = [
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'port' => 25,
            'encryption' => '',
            'starttls' => false,
            'context' => []
        ];
        if (false === in_array($this->transport['encryption'], $this->allowedEncryptions)) {
            $this->transport['encryption'] = '';
        }
    }

    /**
     * @param $from string
     * @param $to mixed
     * @param $message string
     * @param null $headers
     * @return bool
     */
    public function send(string $from, mixed $to, string $message, $headers = null): bool
    {
        $socket = null;
        try {
            $socket = $this->connect();
            fclose($socket);
        } catch (\Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            if ($this->debugMode) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            return false;
        }
        return true;
    }

    /**
     * @return false|resource
     */
    protected function connect()
    {
        $socket = null;
        if (($socket !== null) && $this->transport['encryption'] && !$this->transport['starttls']) {
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_ANY_CLIENT) === false) {
                throw new Exception('Невозможно ' . $this->transport['encryption'] . ' шифрование', 500);
            }
        }
        if (null !== $socket) {
            return $socket;
        }
        return $this->connectDirectly();
    }

    private function connectDirectly()
    {
        $protocol = '';
        if ($this->transport['encryption'] === 'ssl') {
            $protocol = 'ssl://';
        }
        $uri = $protocol . $this->transport['host'] . ':' . $this->transport['port'];
        $context = stream_context_create($this->transport['context']);
        $socket = stream_socket_client($uri, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new Exception(sprintf("Ошибка подключения к '%s' (%s) (%s)", $uri, $errno, $errstr), 500);
        }
        return $socket;
    }

}