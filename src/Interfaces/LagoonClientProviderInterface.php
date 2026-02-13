<?php

declare(strict_types=1);

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces;

use FreedomtechHosting\FtLagoonPhp\Client as LagoonClient;
use FreedomtechHosting\PolydockApp\PolydockServiceProviderInterface;

interface LagoonClientProviderInterface extends PolydockServiceProviderInterface
{
    public function getLagoonClient(): LagoonClient;
}
