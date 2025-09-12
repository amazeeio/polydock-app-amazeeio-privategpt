<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Traits;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Client\AmazeeAiClient;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Exceptions\AmazeeAiClientException;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\TeamResponse;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LoggerInterface;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceStatusFlowException;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Traits\UsesAmazeeAi;


/**
 * Trait UsesAmazeeAiDevmode
 * @package Amazeeio\PolydockAppAmazeeioPrivateGpt\Traits
 * 
 * This trait extends the UsesAmazeeAi trait to provide a development mode.
 * When devModeOverride is set to true, the trait will bypass actual API calls
 * and return mock data instead. This is useful for testing and development
 * environments where interaction with the real amazee.ai API is not desired.
 * 
 * TODO: we can probably make this something like a mock and inject values to be returned
 *      alternatively, we change the entire thing to skip the api under
 *      certain conditions.
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
    public function setAmazeeAiClientDevMode(): void {
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
        return $this->originalPingAmazeeAi();
    }

    public function createTeamAndSetupAdministrator(PolydockAppInstanceInterface $appInstance): TeamResponse
    {
        if ($this->devModeOverride) {
            return new TeamResponse("devmode-name", "devmode-email@example.com", 1, true, true, new \DateTimeImmutable());
        }
        return $this->originalCreateTeamAndSetupAdministrator($appInstance);
    }

    /**
     * @return array{team_id: string, llm_keys: \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\LlmKeysResponse, vdb_keys: \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\VdbKeysResponse}
     */
    public function generateKeysForTeam(PolydockAppInstanceInterface $appInstance, string $teamId): array
    {
        if ($this->devModeOverride) {
            return [
                'team_id' => 'devmode-team-id',
                'llm_keys' => new \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\LlmKeysResponse(1),
                'vdb_keys' => new \Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto\VdbKeysResponse(1, "token", "https://api.example.com", "region", "name"),
            ];
        }
        return $this->originalGenerateKeysForTeam($appInstance, $teamId);
    }

    public function getTeamDetails(string $teamId): TeamResponse
    {
        if ($this->devModeOverride) {
            return new TeamResponse("devmode-name", "devmode-email@example.com", 1, true, true, new \DateTimeImmutable());
        }
        return $this->originalGetTeamDetails($teamId);
    }
}
