<?php

namespace Asifulmamun\DianasmsEsms\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array send(string|array $recipient, string $message, array $options = [])
 */
class Esms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Asifulmamun\DianasmsEsms\EsmsClient::class;
    }
}
