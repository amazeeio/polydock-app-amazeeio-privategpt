<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Traits\Create;

use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\AmazeeAiOperationsInterface;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LagoonOperationsInterface;
use Amazeeio\PolydockAppAmazeeioPrivateGpt\Interfaces\LoggerInterface;
use FreedomtechHosting\PolydockApp\Enums\PolydockAppInstanceStatus;
use FreedomtechHosting\PolydockApp\PolydockAppInstanceInterface;

trait PostCreateAppInstanceTrait
{
    protected ?LoggerInterface $postCreateLogger = null;

    protected ?LagoonOperationsInterface $postCreateLagoonOps = null;

    protected ?AmazeeAiOperationsInterface $postCreateAmazeeAiOps = null;

    /**
     * Setup trait dependencies
     */
    public function setupPostCreateTrait(
        ?LoggerInterface $logger = null,
        ?LagoonOperationsInterface $lagoonOps = null,
        ?AmazeeAiOperationsInterface $amazeeAiOps = null
    ): void {
        $this->postCreateLogger = $logger;
        $this->postCreateLagoonOps = $lagoonOps;
        $this->postCreateAmazeeAiOps = $amazeeAiOps;
    }

    /**
     * Ensure trait is initialized with dependencies
     */
    private function ensurePostCreateTraitInitialized(): void
    {
        if ($this->postCreateLogger === null && $this instanceof LoggerInterface) {
            $this->postCreateLogger = $this;
        }
        if ($this->postCreateLagoonOps === null && $this instanceof LagoonOperationsInterface) {
            $this->postCreateLagoonOps = $this;
        }
        if ($this->postCreateAmazeeAiOps === null && $this instanceof AmazeeAiOperationsInterface) {
            $this->postCreateAmazeeAiOps = $this;
        }
    }

    public function postCreateAppInstance(PolydockAppInstanceInterface $appInstance): PolydockAppInstanceInterface
    {
        $this->ensurePostCreateTraitInitialized();

        $functionName = __FUNCTION__;
        $logContext = $this->postCreateLogger?->getLogContext($functionName) ?? [];
        $testLagoonPing = true;
        $validateLagoonValues = true;
        $validateLagoonProjectName = true;
        $validateLagoonProjectId = true;

        $this->postCreateLogger?->info($functionName.': starting', $logContext);

        $this->postCreateLagoonOps?->validateAndSetupLagoon(
            $appInstance,
            PolydockAppInstanceStatus::PENDING_POST_CREATE,
            $logContext,
            $testLagoonPing,
            $validateLagoonValues,
            $validateLagoonProjectName,
            $validateLagoonProjectId
        );

        $this->postCreateAmazeeAiOps?->setAmazeeAiClientFromAppInstance($appInstance);

        $projectName = $appInstance->getKeyValue('lagoon-project-name');

        $this->postCreateLogger?->info($functionName.': starting for project: '.$projectName, $logContext);
        $appInstance->setStatus(
            PolydockAppInstanceStatus::POST_CREATE_RUNNING,
            PolydockAppInstanceStatus::POST_CREATE_RUNNING->getStatusMessage()
        )->save();

        try {
            if ($this->lagoonClient) {
                $addGroupToProjectResult = $this->lagoonClient->addGroupToProject(
                    $appInstance->getKeyValue('lagoon-deploy-group-name'),
                    $projectName
                );

                if (isset($addGroupToProjectResult['error'])) {
                    $this->postCreateLogger?->error($addGroupToProjectResult['error'][0]['message'], $logContext);
                    throw new \Exception($addGroupToProjectResult['error'][0]['message']);
                }

                if (! isset($addGroupToProjectResult['addGroupsToProject']) || ! isset($addGroupToProjectResult['addGroupsToProject']['id'])) {
                    $this->postCreateLogger?->error('addGroupsToProject ID not found in data', $logContext);
                    throw new \Exception('addGroupsToProject ID not found in data');
                }
            }

            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_APP_NAME', $appInstance->getApp()->getAppName(), 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_GENERATED_APP_ADMIN_USERNAME', $appInstance->getKeyValue('lagoon-generate-app-admin-username'), 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_GENERATED_APP_ADMIN_PASSWORD', $appInstance->getKeyValue('lagoon-generate-app-admin-password'), 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_USER_FIRST_NAME', $appInstance->getKeyValue('user-first-name'), 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_USER_LAST_NAME', $appInstance->getKeyValue('user-last-name'), 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_USER_EMAIL', $appInstance->getKeyValue('user-email'), 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_APP_INSTANCE_HEALTH_WEBHOOK_URL', $appInstance->getKeyValue('polydock-app-instance-health-webhook-url'), 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'LAGOON_FEATURE_FLAG_INSIGHTS', 'false', 'GLOBAL');

            // Set the user's selected region information from the store
            /** @phpstan-ignore-next-line */
            $storeName = $appInstance->storeApp->store->name;
            /** @phpstan-ignore-next-line */
            $storeId = $appInstance->storeApp->store->id;
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_USER_SELECTED_REGION_NAME', $storeName, 'GLOBAL');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'POLYDOCK_USER_SELECTED_REGION_ID', $storeId, 'GLOBAL');

            sleep(2);
            $this->postCreateLogger?->info($functionName.': injecting amazee.ai direct API credentials', $logContext);

            $amazeeAiBackendToken = $appInstance->getKeyValue('amazee-ai-backend-token');
            $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'AMAZEE_AI_BACKEND_TOKEN', $amazeeAiBackendToken, 'GLOBAL');

            $teamId = $appInstance->getKeyValue('amazee-ai-team-id');
            if ($teamId) {
                $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'AMAZEE_AI_TEAM_ID', $teamId, 'GLOBAL');
            }

            $teamCredentials = $appInstance->getKeyValue('amazee-ai-team-credentials');
            if ($teamCredentials) {
                $credentials = json_decode($teamCredentials, true);
                if (isset($credentials['llm_keys']) && isset($credentials['vdb_keys'])) {
                    foreach ($credentials['llm_keys'] as $key => $value) {
                        $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'AI_LLM_'.strtoupper($key), $value, 'GLOBAL');
                    }
                    foreach ($credentials['vdb_keys'] as $key => $value) {
                        $this->postCreateLagoonOps?->addOrUpdateLagoonProjectVariable($appInstance, 'AI_VDB_'.strtoupper($key), $value, 'GLOBAL');
                    }
                }
            }

            $this->postCreateLogger?->info($functionName.': completed injecting amazee.ai direct API credentials', $logContext);

        } catch (\Exception $e) {
            $this->postCreateLogger?->error('Post Create Failed: '.$e->getMessage(), $logContext);
            $appInstance->setStatus(PolydockAppInstanceStatus::POST_CREATE_FAILED, 'An exception occurred: '.$e->getMessage())->save();

            return $appInstance;
        }

        $this->postCreateLogger?->info($functionName.': completed', $logContext);
        $appInstance->setStatus(PolydockAppInstanceStatus::POST_CREATE_COMPLETED, 'Post-create completed')->save();

        return $appInstance;
    }
}
