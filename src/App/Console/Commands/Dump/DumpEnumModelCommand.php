<?php

namespace LaravelDev\App\Console\Commands\Dump;

use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\EnumServices;
use ReflectionException;

class DumpEnumModelCommand extends BaseCommand
{
    protected $name = 'de';
    protected $description = 'Dump Enum Model';

    /**
     * @return int
     * @throws Err
     * @throws ReflectionException
     */
    public function handle(): int
    {
        list($name,) = $this->getNameAndForce();

        $enum = EnumServices::GetEnumModelByClass($name);
        $enum->dump();

        return self::SUCCESS;
    }
}
