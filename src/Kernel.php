<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    // On Vercel the code folder is read-only; only /tmp is writable.
    public function getCacheDir(): string
    {
        return self::isServerless() ? '/tmp/symfony/cache/' . $this->environment : parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        return self::isServerless() ? '/tmp/symfony/log' : parent::getLogDir();
    }

    private static function isServerless(): bool
    {
        return (bool) ($_SERVER['VERCEL'] ?? $_ENV['VERCEL'] ?? getenv('VERCEL'));
    }
}
