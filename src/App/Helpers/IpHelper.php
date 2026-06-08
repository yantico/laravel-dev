<?php

namespace LaravelDev\App\Helpers;

class IpHelper
{
    /**
     * @return string|null
     */
    public static function GetIp(): ?string
    {
        return request()->ip();
    }
}
