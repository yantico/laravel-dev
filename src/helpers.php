<?php

use LaravelDev\App\Exceptions\Err;

if (!function_exists('ee')) {
    /**
     * @throws Err
     */
    function ee(string $message = "", ?int $code = 999, ?string $description = null, ?int $showType = null, ?int $httpStatus = 500): void
    {
        Err::Throw($message, $code, $description, $showType, $httpStatus);
    }

    /**
     * @param int|null $n
     * @return string
     */
    function nt(?int $n = 0): string
    {
        return str_repeat("\t", $n);
    }
}
