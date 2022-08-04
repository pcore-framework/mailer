<?php

declare(strict_types=1);

namespace PCore\Mailer\Transport;

use PCore\Mailer\Constant;
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
     * Текст журнала отладки
     *
     * @var ?string
     */
    private ?string $logText;

    /**
     * Разрешенные шифрования
     *
     * @var array
     */
    private array $allowedEncryptions = ['ssl', 'tls'];

    public function __construct(array $config = [])
    {
        $this->transport = array_merge([
            'host' => 'localhost',
            'username' => '',
            'password' => '',
            'port' => 25,
            'encryption' => '',
            'starttls' => false,
            'httpProxy' => '',
            'context' => []
        ], $config);
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
        $eof = Constant::EOF;
        $this->logText = null;
        $socket = null;
        try {
            $socket = $this->connect();
            $this->serverParse($socket, '220');
            $this->socketSend($socket, 'EHLO ' . $this->transport['host'] . $eof);
            $this->serverParse($socket, '250');
            $this->startTls($socket);
            $this->auth($socket);
            $this->socketSend($socket, "MAIL FROM:<{$from}>" . $eof);
            $this->serverParse($socket, '250');
            $to = is_string($to) ? [$to] : $to;
            foreach ($to as $key => $value) {
                $email = is_string($key) ? $key : $value;
                $this->socketSend($socket, "RCPT TO:<{$email}>" . $eof);
                $this->serverParse($socket, '250');
            }
            $this->socketSend($socket, 'DATA' . $eof);
            $this->serverParse($socket, '354');
            $this->socketSend($socket, trim($headers) . $eof . $eof . trim($message) . $eof);
            $this->socketSend($socket, '.' . $eof);
            $this->serverParse($socket, '250');
            $this->socketSend($socket, 'QUIT' . $eof);
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

    private function connectHttpProxy()
    {
        $host = parse_url($this->transport['httpProxy'], PHP_URL_HOST);
        $port = parse_url($this->transport['httpProxy'], PHP_URL_PORT);
        $user = parse_url($this->transport['httpProxy'], PHP_URL_USER);
        $pass = parse_url($this->transport['httpProxy'], PHP_URL_PASS);
        $creds = '';
        if (($user !== false) && ($pass !== false)) {
            $creds = $user . ':' . $pass;
        }
        $destination = $this->transport['host'] . ':' . $this->transport['port'];
        $socket = stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if ($errno == 0) {
            $auth = $creds ? 'Proxy-Authorization: Basic ' . base64_encode($creds) . "\r\n" : '';
            $connect = "CONNECT $destination HTTP/1.1\r\n$auth\r\n";
            fwrite($socket, $connect);
            $rsp = fread($socket, 1024);
            if (preg_match('/^HTTP\/\d\.\d 200/', $rsp) == 1) {
                return $socket;
            }
            throw new Exception("Запрос отклонен, $rsp\n");
        }
        throw new Exception('Подключиться прокси-сервер ' . $host . ':' . $port . ' не удалось: ' . $errno . ' ' . $errstr);
    }
    
    /**
     * @return resource
     */
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

    /**
     * Анализатор ответов сервера
     *
     * @param resource $socket
     * @param string $expectedResponse
     * @return void
     */
    protected function serverParse($socket, string $expectedResponse)
    {
        $serverResponse = '';
        while (substr($serverResponse, 3, 1) != ' ') {
            if (!($serverResponse = fgets($socket, 256))) {
                throw new Exception('Ошибка при получении кодов ответов сервера.' . __FILE__ . __LINE__, 500);
            }
            $this->logText .= date('Y-m-d h:i:s') . ' SERVER -> CLIENT: ' . trim($serverResponse) . "\n";
        }
        if (!(substr($serverResponse, 0, 3) == $expectedResponse)) {
            throw new Exception("Не удалось отправить электронное письмо.{$serverResponse}" . __FILE__ . __LINE__, 500);
        }
    }

    /**
     * @param $socket
     * @param $message
     */
    protected function socketSend($socket, $message)
    {
        $this->logText .= date('Y-m-d h:i:s') . ' CLIENT -> SERVER: ' . $message;
        fwrite($socket, $message);
    }

    /**
     * @param $socket
     */
    private function startTls($socket)
    {
        $eof = Constant::EOF;
        if (($this->transport['encryption'] === 'tls') && $this->transport['starttls']) {
            $this->socketSend($socket, 'STARTTLS' . $eof);
            $this->serverParse($socket, '220');
            if (false === stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('Не удалось запустить tls-шифрование', 500);
            }
            $this->socketSend($socket, 'EHLO ' . $this->transport['host'] . $eof);
            $this->serverParse($socket, '250');
        }
    }

    /**
     * @param $socket
     */
    private function auth($socket)
    {
        $eof = Constant::EOF;
        if ($this->transport['username'] && $this->transport['password']) {
            $this->socketSend($socket, 'AUTH LOGIN' . $eof);
            $this->serverParse($socket, '334');
            $this->socketSend($socket, base64_encode($this->transport['username']) . $eof);
            $this->serverParse($socket, '334');
            $this->socketSend($socket, base64_encode($this->transport['password']) . $eof);
            $this->serverParse($socket, '235');
        }
    }

}
