<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Traits\Create;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\AmazeeAiOperationsInterface;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LagoonOperationsInterface;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LoggerInterface;
use FreedomtechHosting\PolydockApp\Enums\PolydockAppInstanceStatus;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface;

trait PreCreateAppInstanceTrait
{
    protected ?LoggerInterface $preCreateLogger = null;

    protected ?LagoonOperationsInterface $preCreateLagoonOps = null;

    protected ?AmazeeAiOperationsInterface $preCreateAmazeeAiOps = null;

    /**
     * Setup trait dependencies
     */
    public function setupPreCreateTrait(
        ?LoggerInterface $logger = null,
        ?LagoonOperationsInterface $lagoonOps = null,
        ?AmazeeAiOperationsInterface $amazeeAiOps = null
    ): void {
        $this->preCreateLogger = $logger;
        $this->preCreateLagoonOps = $lagoonOps;
        $this->preCreateAmazeeAiOps = $amazeeAiOps;
    }

    /**
     * Ensure trait is initialized with dependencies
     */
    private function ensurePreCreateTraitInitialized(): void
    {
        if ($this->preCreateLogger === null && $this instanceof LoggerInterface) {
            $this->preCreateLogger = $this;
        }
        if ($this->preCreateLagoonOps === null && $this instanceof LagoonOperationsInterface) {
            $this->preCreateLagoonOps = $this;
        }
        if ($this->preCreateAmazeeAiOps === null && $this instanceof AmazeeAiOperationsInterface) {
            $this->preCreateAmazeeAiOps = $this;
        }
    }

    public function preCreateAppInstance(PolydockAppInstanceInterface $appInstance): PolydockAppInstanceInterface
    {
        $this->ensurePreCreateTraitInitialized();

        $functionName = __FUNCTION__;
        $logContext = $this->preCreateLogger?->getLogContext($functionName) ?? [];
        $testLagoonPing = true;
        $validateLagoonValues = true;
        $validateLagoonProjectName = true;
        $validateLagoonProjectId = false;

        $this->preCreateLogger?->info($functionName.': starting', $logContext);

        $this->preCreateLagoonOps?->validateAndSetupLagoon(
            $appInstance,
            PolydockAppInstanceStatus::PENDING_PRE_CREATE,
            $logContext,
            $testLagoonPing,
            $validateLagoonValues,
            $validateLagoonProjectName,
            $validateLagoonProjectId
        );

        $this->preCreateAmazeeAiOps?->setAmazeeAiClientFromAppInstance($appInstance);

        $projectName = $appInstance->getKeyValue('lagoon-project-name');

        $this->preCreateLogger?->info($functionName.': starting for project: '.$projectName, $logContext);
        $appInstance->setStatus(
            PolydockAppInstanceStatus::PRE_CREATE_RUNNING,
            PolydockAppInstanceStatus::PRE_CREATE_RUNNING->getStatusMessage()
        )->save();

        $team = $this->preCreateAmazeeAiOps?->createTeamAndSetupAdministrator($appInstance);

        if ($team) {
            $appInstance->storeKeyValue('amazee-ai-team-id', (string) $team->id);
            $appInstance->storeKeyValue('amazee-ai-team-name', $team->name);
        }

        // Here we want to override the app admin username to the incoming registration email
        // TODO: this is quite nasty, and should be supported by polydock core
        $adminEmail = $appInstance->getKeyValue('user-email');
        $appInstance->storeKeyValue('lagoon-generate-app-admin-username', $adminEmail); // since this is saved below, let's see if this works.

        $this->preCreateLogger?->info($functionName.': completed', $logContext);
        $appInstance->setStatus(PolydockAppInstanceStatus::PRE_CREATE_COMPLETED, 'Pre-create completed')->save();

        return $appInstance;
    }
}
