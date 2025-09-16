<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\TeamResponse;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface;

interface AmazeeAiOperationsInterface
{
    /**
     * Setup AmazeeAi client from app instance configuration
     */
    public function setAmazeeAiClientFromAppInstance(PolydockAppInstanceInterface $appInstance): void;

    /**
     * Create team and setup administrator
     */
    public function createTeamAndSetupAdministrator(PolydockAppInstanceInterface $appInstance): TeamResponse;

    /**
     * Generate keys for a team
     *
     * @return array{team_id: string, backend_key: \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\APIToken}
     */
    public function generateKeysForTeam(PolydockAppInstanceInterface $appInstance, string $teamId): array;
}
