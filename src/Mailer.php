<?php

declare(strict_types=1);

namespace PCore\Mailer;

use InvalidArgumentException;
use PCore\Mailer\Contracts\TransportInterface;

/**
 * Class Mailer
 * @package PCore\Mailer
 * @github https://github.com/pcore-framework/mailer
 */
class Mailer
{

    /**
     * @var string
     */
    protected string $toEmail;

    /**
     * @var string
     */
    protected string $toName;

    /**
     * @var string
     */
    protected string $fromEmail;

    /**
     * @var string
     */
    protected string $fromName;

    /**
     * @var string
     */
    protected string $subject;

    /**
     * @var string
     */
    protected string $text;

    /**
     * @var TransportInterface
     */
    protected TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->toName = '';
        $this->toEmail = '';
        $this->fromName = '';
        $this->fromEmail = '';
        $this->subject = '';
        $this->text = '';
        $this->transport = $transport;
    }

    public function setTo($email, $name = '')
    {
        $this->toEmail = $email;
        $this->toName = $name;
        return $this;
    }

    public function getToName()
    {
        return $this->toName;
    }

    public function setFrom($email, $name = '')
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
        return $this;
    }

    public function getFromName()
    {
        return $this->fromName;
    }

    public function setReplyTo($email, $name = false)
    {
        $this->replyEmail = $email;
        $this->replyName = $name;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Отправляет составленное письмо с выбранным транспортом.
     *
     * @return boolean
     */
    public function send()
    {
        if (!$this->toEmail) {
            throw new InvalidArgumentException('Ошибка: электронная почта обязательна!');
        }
        if (!$this->fromEmail) {
            throw new InvalidArgumentException('Ошибка: электронная почта от обязательно!');
        }
        if (!$this->subject) {
            throw new InvalidArgumentException('Ошибка: требуется тема электронной почты!');
        }
        if (!$this->text) {
            throw new InvalidArgumentException('Ошибка: требуется сообщение по электронной почте!');
        }
        $mime = $this->generateMime();
        return $this->transport->send($this->getFromEmail(), $this->getToEmail(), $mime['message'], $mime['headers']);
    }

    public function generateMime(): array
    {
        $eol = Constant::EOF;
        $headers = '';
        if (is_array($this->toEmail)) {
            $toEmails = [];
            foreach ($this->toEmail as $key => $value) {
                $toEmails[] = is_int($key) ? $this->formatEmail($value) : $this->formatEmail($key, $value);
            }
            $to = implode(', ', $toEmails);
        } elseif ($this->toName) {
            $to = $this->formatEmail($this->toEmail, $this->toName);
        } else {
            $to = $this->formatEmail($this->toEmail);
        }
        $toHeader = 'To: ' . $to . $eol;
        if ($this->hasNotUnicode($this->subject)) {
            $subject = $this->subject;
        } else {
            $subject = '=?UTF-8?B?' . base64_encode($this->subject) . '?=';
        }
        $subjectHeader = 'Subject: ' . $subject . $eol;
        $message = '';
        $headers .= 'MIME-Version: 1.0' . $eol;
        $from = $this->formatEmail($this->fromEmail, $this->fromName);
        $headers .= 'From: ' . $from . $eol;
        $replyTo = $from;
        $headers .= 'Reply-To: ' . $replyTo . $eol;
        $headers = $toHeader . $subjectHeader . $headers;
        return compact('from', 'to', 'headers', 'message');
    }

    private function formatEmail($email, $name = ''): string
    {
        $emailFormatted = '<' . $email . '>';
        if (!$name) {
            return $emailFormatted;
        }
        if ($this->hasNotUnicode($name)) {
            return $name . ' ' . $emailFormatted;
        }
        return '=?UTF-8?B?' . base64_encode($name) . '?= ' . $emailFormatted;
    }

    private function hasNotUnicode($str): bool|int
    {
        return preg_match('/^[a-zA-Z0-9\-\. ]+$/', $str);
    }

    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    public function getToEmail()
    {
        return $this->toEmail;
    }

}