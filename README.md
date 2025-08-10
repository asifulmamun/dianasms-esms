# dianasms-esms (Laravel ESMS)

Send SMS via ESMS (login.esms.com.bd) from Laravel. Supports multiple recipients and scheduling.

## Install
```bash
composer require asifulmamun/dianasms-esms
php artisan vendor:publish --tag=config --provider="Asifulmamun\DianasmsEsms\EsmsServiceProvider"
```


## Set .env collect from https://www.dianahost.com/bulk-sms-service/
```
ESMS_BASE_URL=https://login.esms.com.bd
ESMS_API_TOKEN="XXXXXXXXXXXXXXXX"
ESMS_SENDER_ID="XXXXXXXX"
ESMS_TYPE=plain
ESMS_TIMEOUT=10
ESMS_HTTP_MODE=json
```

## Use
```
use Asifulmamun\DianasmsEsms\Esms;
Esms::send('01721600688', 'Your OTP: XXXX');
```
### Or
`\Asifulmamun\DianasmsEsms\Esms::send('01721600688', 'Your OTP: XXXX');`


## Use Overriding
```
// override base_url + token (e.g., staging) for this call
Esms::send('01721600688', 'Hello', [
    'base_url'  => 'https://login.esms.com.bd',
    'api_token' => 'xxxxxxxxxxx',
    'timeout'   => 20,
    'sender_id' => 'xxxxxxxxx',
    'http_mode' => 'json',
]);
```
