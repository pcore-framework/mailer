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
     * @var array
     */
    protected array $attachments;

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
     * @var bool|string
     */
    protected bool|string $messageId = false;

    /**
     * @var bool|string
     */
    protected bool|string $xMailer = false;

    /**
     * @var bool|DateTimeImmutable
     */
    protected bool|DateTimeImmutable $date = false;

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
        $this->attachments = [];
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
     * @param $priority string ?????????? ???????? 'normal', 'urgent', 'non-urgent'
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
     * @param $attachment
     * @param string $inlineFileName
     * @return $this
     */
    public function attach($attachment, $inlineFileName = ''): Mailer
    {
        $basename = $inlineFileName ?: basename($attachment);
        $this->attachments[$basename] = $attachment;
        $this->contentIds[$basename] = $this->genCid($basename);
        return $this;
    }

    /**
     * @param $name
     * @return string
     */
    private function genCid($name): string
    {
        return md5($name);
    }

    /**
     * @return array
     */
    public function getAttachments(): array
    {
        return $this->attachments;
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
     * ???????????????????? ???????????????????????? ???????????? ?? ?????????????????? ??????????????????????.
     *
     * @return boolean
     */
    public function send()
    {
        if (!$this->toEmail) {
            throw new InvalidArgumentException('????????????: ?????????????????????? ?????????? ??????????????????????!');
        }
        if (!$this->fromEmail) {
            throw new InvalidArgumentException('????????????: ?????????????????????? ?????????? ???? ??????????????????????!');
        }
        if (!$this->subject) {
            throw new InvalidArgumentException('????????????: ?????????????????? ???????? ?????????????????????? ??????????!');
        }
        if (!$this->text && !$this->html) {
            throw new InvalidArgumentException('????????????: ?????????????????? ?????????????????? ???? ?????????????????????? ??????????!');
        }
        $priorities = [Constant::PRIORITY_NORMAL, Constant::PRIORITY_URGENT, Constant::PRIORITY_NON_URGENT];
        if ($this->priority && !in_array($this->priority, $priorities, true)) {
            throw new InvalidArgumentException("?????????????????? ?????????????????? ???????????????? ?? " . join(', ', $priorities));
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
        $type = ($this->html && $this->text) ? 'alt' : 'plain';
        $type .= count((array)$this->attachments) ? '_attachments' : '';
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
        $boundary = match ($type) {
            'alt', 'plain_attachments', 'alt_attachments' => $this->genBoundaryId(),
            default => '',
        };
        $headers .= match ($type) {
            'plain' => 'Content-Type: ' . ($this->html ? 'text/html' : 'text/plain') . '; charset="UTF-8"',
            'alt' => 'Content-Type: multipart/alternative; format=flowed; delsp=yes; boundary="' .
                $boundary . '"',
            'plain_attachments', 'alt_attachments' => 'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        };
        switch ($type) {
            case 'plain':
                $message .= $this->html ?: $this->text;
                break;
            case 'alt':
                $message .= '--' . $boundary . $eol;
                $message .= $this->genEncodeTextPart($this->text);
                $message .= $eol . '--' . $boundary . $eol;
                $message .= $this->genEncodeHtmlPart($this->html);
                break;

            case 'plain_attachments':
                $message .= '--' . $boundary . $eol;
                if ($this->text) {
                    $message .= $this->genEncodeTextPart($this->text);
                } else {
                    $message .= $this->genEncodeHtmlPart($this->embedAttachments($this->html));
                }
                break;
            case 'alt_attachments':
                $boundary2 = 'bd2_' . $boundary;
                $message .= '--' . $boundary . $eol;
                $message .= 'Content-Type: multipart/alternative; boundary="' . $boundary2 . '"' . $eol . $eol;
                $message .= '--' . $boundary2 . $eol;
                $message .= $this->genEncodeTextPart($this->text);
                $message .= $eol . '--' . $boundary2 . $eol;
                $message .= $this->genEncodeHtmlPart($this->embedAttachments($this->html));
                $message .= $eol . '--' . $boundary2 . '--';
                break;
        }
        switch ($type) {
            case 'plain_attachments':
            case 'alt_attachments':
                foreach ($this->attachments as $basename => $fullname) {
                    $content = file_get_contents($fullname);
                    $message .= $eol . '--' . $boundary . $eol;
                    $message .= $this->genAttachHeaderPart($this->contentIds[$basename], $basename);
                    $message .= chunk_split(base64_encode($content), 76, $eol);
                }
                break;
        }
        switch ($type) {
            case 'alt':
            case 'plain_attachments':
            case 'alt_attachments':
                $message .= $eol . '--' . $boundary . '--';
                break;
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
    protected function genBoundaryId(): string
    {
        return md5(uniqid(time(), true));
    }

    /**
     * @param $text
     * @return string
     */
    private function genEncodeTextPart($text): string
    {
        $eol = Constant::EOF;
        $message = 'Content-Type: text/plain; charset="UTF-8"' . $eol;
        $message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
        $message .= chunk_split(base64_encode($text), 76, $eol);
        return $message;
    }

    /**
     * @param $html
     * @return string
     */
    private function genEncodeHtmlPart($html): string
    {
        $eol = Constant::EOF;
        $message = 'Content-Type: text/html; charset="UTF-8"' . $eol;
        $message .= 'Content-Transfer-Encoding: base64' . $eol . $eol;
        $message .= chunk_split(base64_encode($html), 76, $eol);
        return $message;
    }

    /**
     * @param $html
     * @return string
     */
    private function embedAttachments($html): string
    {
        $patterns = [];
        $replacements = [];
        foreach ($this->contentIds as $basename => $id) {
            $patterns[] = '/' . preg_quote($basename) . '/ui';
            $replacements[] = 'cid:' . $id;
        }
        return preg_replace($patterns, $replacements, $html);
    }

    /**
     * @param $cid
     * @param $basename
     * @return string
     */
    private function genAttachHeaderPart($cid, $basename): string
    {
        $eol = Constant::EOF;
        $message = 'Content-Type: application/octetstream' . $eol;
        $message .= 'Content-Transfer-Encoding: base64' . $eol;
        $message .= 'Content-Disposition: attachment; filename="' . $basename . '"' . $eol;
        $message .= 'Content-ID: <' . $cid . '>' . $eol . $eol;
        return $message;
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