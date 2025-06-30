<?php

namespace Amazeelabs\PolydockAppAmazeeioPrivateGpt\Interfaces;

use FreedomtechHosting\PolydockApp\PolydockAppBase;

interface LoggerInterface
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $message, array $context = []): PolydockAppBase;

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(string $message, array $context = []): PolydockAppBase;

    /**
     * @return array<string, mixed>
     */
    public function getLogContext(string $location): array;
}
