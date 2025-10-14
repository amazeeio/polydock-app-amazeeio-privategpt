# Integration Guide: polydock-app-amazeeio-privategpt

This guide covers how to integrate the PrivateGPT app package into your Polydock Engine instance.

## Installation

The package has been configured as a local path dependency in the main `polydock-engine/composer.json`.

To install the dependency:

```bash
cd polydock-engine/
composer install
```

## Setting up a PrivateGPT Store App

To use the PrivateGPT app, create a new `PolydockStoreApp` with the following configuration:

### Database Entry Example

```php
\App\Models\PolydockStoreApp::create([
    'polydock_store_id' => $store->id,
    'name' => 'PrivateGPT with Direct API',
    'polydock_app_class' => 'FreedomtechHosting\PolydockAppAmazeeioPrivateGpt\PolydockPrivateGptApp',
    'description' => 'PrivateGPT application with direct amazee.ai integration',
    'author' => 'Your Organization',
    'website' => 'https://your-website.com/',
    'support_email' => 'support@your-organization.com',
    'lagoon_deploy_git' => 'git@github.com:your-org/privategpt-application.git',
    'lagoon_deploy_branch' => 'main',
    'status' => \App\Enums\PolydockStoreAppStatusEnum::AVAILABLE,
    'available_for_trials' => true,
    'target_unallocated_app_instances' => 0,
    'lagoon_post_deploy_script' => '/app/.lagoon/scripts/polydock_post_deploy.sh',
    'lagoon_claim_script' => '/app/.lagoon/scripts/polydock_claim.sh',
]);
```

### Required Configuration Variables

The following variables must be configured in your Polydock Store:

#### Basic Lagoon Configuration
- `lagoon-deploy-git`: Repository containing your PrivateGPT application
- `lagoon-deploy-branch`: Branch to deploy (typically 'main')
- `lagoon-deploy-region-id`: Lagoon region ID for deployment
- `lagoon-deploy-private-key`: SSH private key for deployment
- `lagoon-deploy-organization-id`: Lagoon organization ID
- `lagoon-deploy-project-prefix`: Project name prefix
- `lagoon-deploy-group-name`: Lagoon group for deployment

#### amazee.ai Direct API Configuration
- `amazee-ai-api-key`: Your amazee.ai API key
- `amazee-ai-api-url`: amazee.ai API URL (defaults to 'https://api.amazee.ai')
- `user-email`: Email address for team administrator - this comes from the polydock registration payload

## PrivateGPT Application Requirements

Your PrivateGPT application repository must include:

### 1. Lagoon Configuration
Standard Lagoon configuration files as required for deployment.

### 2. Deployment Scripts
- `/app/.lagoon/scripts/polydock_post_deploy.sh`: Post-deployment script
- `/app/.lagoon/scripts/polydock_claim.sh`: Claim script (see example below)

### 3. Claim Script Example

The claim script will have access to the following environment variables:

```bash
#!/bin/bash
# /app/.lagoon/scripts/polydock_claim.sh

# Environment variables automatically injected by PolydockPrivateGptApp:
# - AMAZEE_AI_API_KEY: Direct API key for amazee.ai
# - AMAZEE_AI_TEAM_ID: Team ID created for this deployment
# - AI_LLM_*: LLM credentials (keys vary based on amazee.ai response)
# - AI_VDB_*: Vector database credentials (keys vary based on amazee.ai response)
# - POLYDOCK_USER_EMAIL: User's email address
# - Standard Polydock variables (POLYDOCK_USER_FIRST_NAME, etc.)

if [ -n "$AMAZEE_AI_API_KEY" ]; then
    echo "Configuring PrivateGPT with amazee.ai direct API..."
    
    # Configure your application with the API key
    # This is application-specific - adapt to your PrivateGPT implementation
    
    # Example: Write configuration file
    cat > /app/config/amazee-ai.conf <<EOF
AMAZEE_AI_API_KEY=${AMAZEE_AI_API_KEY}
AMAZEE_AI_TEAM_ID=${AMAZEE_AI_TEAM_ID}
ADMIN_EMAIL=${POLYDOCK_USER_EMAIL}
EOF
    
    # Example: Initialize your application
    # /app/bin/initialize-privategpt.sh
    
    # Your application should handle sending login links automatically
    # The PolydockPrivateGptApp does NOT send login links - your app must do this
    
    # Return the application URL (required)
    echo "https://${LAGOON_ROUTE}"
else
    echo "Error: amazee.ai API key not found" >&2
    exit 1
fi
```

## Workflow Overview

1. **Pre-Create**: Creates team on amazee.ai and sets user as administrator
2. **Create**: Creates Lagoon project and generates LLM/VDB keys
3. **Post-Create**: Injects API keys and credentials as environment variables
4. **Deploy**: Standard Lagoon deployment process
5. **Claim**: Executes claim script with API key available for application configuration

## Key Differences from Generic App

- **Direct API Integration**: No intermediate backend service
- **Team-Based**: Creates amazee.ai teams instead of managing individual users
- **Administrator Role**: Users become team administrators with full control
- **Automated Experience**: Applications handle login link delivery (not Polydock)
- **Custom Variables**: Injects `AMAZEE_AI_API_KEY`, `AMAZEE_AI_TEAM_ID`, and credential keys

## Testing

To test the integration:

1. Ensure your amazee.ai API key is valid and has team creation permissions
2. Create a test PolydockStoreApp with the PrivateGPT class
3. Deploy a test PrivateGPT application that includes the required claim script
4. Verify team creation, key generation, and application configuration work correctly

## Troubleshooting

- Check logs for amazee.ai API errors during team creation
- Verify API key permissions for team management
- Ensure claim script properly handles environment variables
- Validate application can access injected credentials