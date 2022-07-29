<?php

declare(strict_types=1);

namespace PCore\Mailer;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
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
    protected string $replyName;

    /**
     * @var string
     */
    protected string $replyEmail;

    /**
     * @var string
     */
    protected string $subject;

    /**
     * @var string
     */
    protected string $text;

    /**
     * @var string
     */
    protected string $html;

    /**
     * @var string
     */
    protected string $priority;

    /**
     * @var array
     */
    protected array $customHeaders = [];

    /**
     * @var TransportInterface
     */
    protected TransportInterface $transport;

    /**
     * @var string
     */
    protected string $messageId;

    /**
     * @var string
     */
    protected string $xMailer;

    /**
     * @var DateTimeImmutable
     */
    protected DateTimeImmutable $date;

    /**
     * @var array
     */
    private array $contentIds = [];

    public function __construct(TransportInterface $transport)
    {
        $this->toName = '';
        $this->toEmail = '';
        $this->fromName = '';
        $this->fromEmail = '';
        $this->replyName = '';
        $this->replyEmail = '';
        $this->subject = '';
        $this->text = '';
        $this->html = '';
        $this->priority = '';
        $this->customHeaders = [];
        $this->logText = '';
        $this->transport = $transport;
    }

    /**
     * @param $email
     * @param string $name
     * @return $this
     */
    public function setTo($email, $name = ''): Mailer
    {
        $this->toEmail = $email;
        $this->toName = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getToName(): string
    {
        return $this->toName;
    }

    /**
     * @param $email
     * @param string $name
     * @return $this
     */
    public function setFrom($email, $name = ''): Mailer
    {
        $this->fromEmail = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * @param $email
     * @param $name
     * @return $this
     */
    public function setReplyTo($email, $name = false): Mailer
    {
        $this->replyEmail = $email;
        $this->replyName = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getReplyToEmail(): string
    {
        return $this->replyEmail;
    }

    /**
     * @return string
     */
    public function getReplyToName(): string
    {
        return $this->replyName;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param $subject
     * @return $this
     */
    public function setSubject($subject): Mailer
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param $text
     * @return $this
     */
    public function setText($text): Mailer
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return string
     */
    public function getHTML(): string
    {
        return $this->html;
    }

    /**
     * @param $html
     * @param false $addAltText
     * @return $this
     */
    public function setHTML($html, $addAltText = false): Mailer
    {
        $this->html = $html;
        if ($addAltText) {
            $this->text = strip_tags($this->html);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getPriority(): string
    {
        return $this->priority;
    }

    /**
     * @param $priority string может быть 'normal', 'urgent', 'non-urgent'
     * @return $this
     */
    public function setPriority(string $priority): Mailer
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @param mixed $messageId
     * @return Mailer
     */
    public function setMessageId(mixed $messageId): Mailer
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * @param $xMailer
     * @return $this
     */
    public function setXMailer($xMailer): Mailer
    {
        $this->xMailer = $xMailer;
        return $this;
    }

    /**
     * @param $name
     * @return string
     */
    private function genCid($name):string
    {
        return md5($name);
    }

    /**
     * @return array
     */
    public function getCustomHeaders(): array
    {
        return $this->customHeaders;
    }

    /**
     * @param array $ch
     * @return $this
     */
    public function setCustomHeaders(array $ch): Mailer
    {
        $this->customHeaders = $ch;

        return $this;
    }

    /**
     * @param DateTimeInterface $date
     * @return $this
     */
    public function setDate(DateTimeInterface $date): Mailer
    {
        $this->date = $date;
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
        if (!$this->text && !$this->html) {
            throw new InvalidArgumentException('Ошибка: требуется сообщение по электронной почте!');
        }
        $priorities = [Constant::PRIORITY_NORMAL, Constant::PRIORITY_URGENT, Constant::PRIORITY_NON_URGENT];
        if ($this->priority && !in_array($this->priority, $priorities, true)) {
            throw new InvalidArgumentException("Приоритет возможных значений в " . join(', ', $priorities));
        }
        $mime = $this->generateMime();
        return $this->transport->send($this->getFromEmail(), $this->getToEmail(), $mime['message'], $mime['headers']);
    }

    /**
     * @return array
     */
    public function generateMime(): array
    {
        $eol = Constant::EOF;
        $headers = '';
        if ($this->messageId) {
            $headers .= 'MessagePart-ID: ' . $this->messageId . $eol;
        }
        if ($this->xMailer) {
            $headers .= 'X-Mailer: ' . $this->xMailer . $eol;
        }
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
        if ($this->replyEmail) {
            $replyTo = $this->replyEmail;
            if ($this->replyName) {
                $replyTo = $this->formatEmail($this->replyEmail, $this->replyName);
            }
        }
        $headers .= 'Reply-To: ' . $replyTo . $eol;
        $date = $this->date ? $this->date : new DateTime();
        $headers .= 'Date: ' . gmdate('D, d M Y H:i:s O', $date->getTimestamp()) . $eol;
        if ($this->priority) {
            $headers .= 'Priority: ' . $this->priority . $eol;
        }
        if (count((array)$this->customHeaders)) {
            foreach ($this->customHeaders as $k => $v) {
                $headers .= $k . ': ' . $v . $eol;
            }
        }
        $headers = $toHeader . $subjectHeader . $headers;
        return compact('from', 'to', 'headers', 'message');
    }

    /**
     * @param $email
     * @param string $name
     * @return string
     */
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

    /**
     * @param $str
     * @return bool|int
     */
    private function hasNotUnicode($str): bool|int
    {
        return preg_match('/^[a-zA-Z0-9\-\. ]+$/', $str);
    }

    /**
     * @return string
     */
    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    /**
     * @return string
     */
    public function getToEmail(): string
    {
        return $this->toEmail;
    }

}