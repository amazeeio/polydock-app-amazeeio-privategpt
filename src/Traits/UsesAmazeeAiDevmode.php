<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Traits;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\TeamResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LoggerInterface;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceStatusFlowException;

/**
 * Trait UsesAmazeeAiDevmode
 */
trait UsesAmazeeAiDevmode
{
    use UsesAmazeeAi {
        setupAmazeeAiTrait as private originalSetupAmazeeAiTrait;
        ensureAmazeeAiTraitInitialized as private originalEnsureAmazeeAiTraitInitialized;
        getAmazeeAiClient as private originalGetAmazeeAiClient;
        setAmazeeAiClientFromAppInstance as private originalSetAmazeeAiClientFromAppInstance;
        pingAmazeeAi as private originalPingAmazeeAi;
        createTeamAndSetupAdministrator as private originalCreateTeamAndSetupAdministrator;
        generateKeysForTeam as private originalGenerateKeysForTeam;
        getTeamDetails as private originalGetTeamDetails;
    }

    protected ?bool $devModeOverride = false;

    /**
     * Set the whole Client into Dev mode
     */
    public function setAmazeeAiClientDevMode(): void
    {
        $this->devModeOverride = true;
    }

    /**
     * Setup the trait dependencies
     */
    public function setupAmazeeAiTrait(?LoggerInterface $logger = null): void
    {
        if ($this->devModeOverride) {
            $this->setAmazeeAiClientDevMode();
        } else {
            $this->originalSetupAmazeeAiTrait($logger);
        }
    }

    /**
     * Ensure trait is initialized with dependencies
     */
    private function ensureAmazeeAiTraitInitialized(): void
    {
        if ($this->devModeOverride) {
            return;
        }
        $this->originalEnsureAmazeeAiTraitInitialized();
    }

    /**
     * @throws PolydockAppInstanceStatusFlowException
     */
    protected function getAmazeeAiClient(): AmazeeAiClient
    {
        return $this->originalGetAmazeeAiClient();
    }

    public function setAmazeeAiClientFromAppInstance(PolydockAppInstanceInterface $appInstance): void
    {
        $this->originalSetAmazeeAiClientFromAppInstance($appInstance);
    }

    public function pingAmazeeAi(): bool
    {
        if ($this->devModeOverride) {
            return true;
        }

        return $this->originalPingAmazeeAi();
    }

    public function createTeamAndSetupAdministrator(PolydockAppInstanceInterface $appInstance): TeamResponse
    {
        if ($this->devModeOverride) {
            return new TeamResponse('devmode-name', 'devmode-email@example.com', 1, true, true, '');
        }

        return $this->originalCreateTeamAndSetupAdministrator($appInstance);
    }

    /**
     * @return array{team_id: string, backend_key: \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\APIToken, llm_key: \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\LlmKeysResponse}
     */
    public function generateKeysForTeam(PolydockAppInstanceInterface $appInstance, string $teamId): array
    {
        if ($this->devModeOverride) {
            return [
                'team_id' => 'devmode-team-id',
                'backend_key' => new \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\APIToken('devmode-token', 1, 'token', 'created-at', 1, 'last-used-at'),
                'llm_key' => new \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\LlmKeysResponse(1, 'database-name-here', 'llmkey-name', 'database-host', 'database-username', 'database-password', 'litellm-token', 'litellm-api-url', 'region-name', 'created-at', 1, 1),
            ];
        }

        return $this->originalGenerateKeysForTeam($appInstance, $teamId);
    }

    public function getTeamDetails(string $teamId): TeamResponse
    {
        if ($this->devModeOverride) {
            return new TeamResponse('devmode-name', 'devmode-email@example.com', 1, true, true, '');
        }

        return $this->originalGetTeamDetails($teamId);
    }
}
