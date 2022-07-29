## Mailer

### Установка

```shell
composer require pcore/mailer
```

```php
use PCore\Mailer\Transport\SMTPTransport;
use PCore\Mailer\Transport\Mailer;

$mailer = new Mailer(new SMTPTransport([
    'host' => 'localhost',
    'port' => 465,
    'encryption' => 'ssl',
    'username' => '',
    'password' => ''
]));

$mailer->setFrom('from@example.com')
    ->setTo('to@example.com')
    ->setSubject('Привет')
    ->setText('Привет мир')
    ->send();
```