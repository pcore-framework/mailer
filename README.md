## Mailer

### Установка

```shell
composer require pcore/mailer
```

```php
use PCore\Mailer\Transport\SMTPTransport;
use PCore\Mailer\Mailer;

$mailer = new Mailer(new SMTPTransport([
    'host' => 'localhost',
    'port' => 465,
    'encryption' => 'ssl',
    'username' => '',
    'password' => '',

    // Использование HTTP-прокси 
    'httpProxy' => 'http://example.com'
]));

$mailer->setFrom('from@example.com')
    ->setTo('to@example.com')
    ->setSubject('Привет')
    ->setText('Привет мир')
    ->send();
```