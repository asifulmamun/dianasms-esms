# dianasms-esms (Laravel ESMS)

Send SMS via ESMS (login.esms.com.bd) from Laravel. Supports multiple recipients and scheduling.

## Install
```bash
composer require asifulmamun/dianasms-esms
php artisan vendor:publish --tag=config --provider="Asifulmamun\DianasmsEsms\EsmsServiceProvider"
