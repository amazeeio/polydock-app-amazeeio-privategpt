#!/bin/bash

# Example polydock_claim.sh script for PrivateGPT applications
# This script should be placed in the PrivateGPT application repository at:
# /app/.lagoon/scripts/polydock_claim.sh

# The amazee.ai API key is automatically injected as AMAZEE_AI_API_KEY environment variable
# by the PolydockPrivateGptApp during the PostCreateAppInstanceTrait execution

# Example of how the application might use the API key:
if [ -n "$AMAZEE_AI_API_KEY" ]; then
    echo "amazee.ai API key is available for application use"
    
    # The application can use this key to:
    # 1. Authenticate with amazee.ai directly
    # 2. Access team resources
    # 3. Send automated login links to users
    
    # Example: Configure the application with the API key
    # This would be application-specific implementation
    cat > /app/config/amazee-ai.conf <<EOF
AMAZEE_AI_API_KEY=${AMAZEE_AI_API_KEY}
AMAZEE_AI_TEAM_ID=${AMAZEE_AI_TEAM_ID}
EOF
    
    # Example: Trigger automatic login link sending
    # This is where the application would implement its own logic
    # to send login links to the user automatically
    if [ -n "$POLYDOCK_USER_EMAIL" ]; then
        echo "Configuring automatic login for user: $POLYDOCK_USER_EMAIL"
        
        # Application-specific implementation would go here
        # For example:
        # /app/bin/send-login-link.php "$POLYDOCK_USER_EMAIL" "$AMAZEE_AI_API_KEY"
    fi
    
    # Output the application URL (required by Polydock)
    # This should be the URL where users can access the application
    echo "https://${LAGOON_ROUTE}"
    
else
    echo "Error: amazee.ai API key not found" >&2
    exit 1
fi