# Polydock App - amazee.io PrivateGPT

This package provides a Polydock app implementation for deploying PrivateGPT applications on amazee.io with direct amazee.ai API integration.

## Features

- Direct amazee.ai API integration (no backend intermediary)
- Team-based deployment architecture
- Automated user onboarding with team administrator privileges
- LLM and VDB key generation
- Seamless integration with Lagoon deployment workflows

## Key Differences from Generic App

Unlike the generic amazee.io app that uses the amazee.ai backend service, this implementation:

1. **Direct API Integration**: Uses `https://api.amazee.ai/` directly
2. **Team Creation**: Creates new teams for each deployment
3. **Administrator Access**: Users become team administrators
4. **Automated Experience**: Applications handle login link delivery automatically

## Architecture

- **PolydockPrivateGptApp**: Main app class extending PolydockApp
- **AmazeeAiClient**: Direct API client for amazee.ai
- **Team Management Traits**: Handle team creation and key generation
- **Custom Deployment Scripts**: Modified for direct API integration

## Usage

This package is designed to be used within the Polydock Engine ecosystem and requires appropriate configuration for amazee.ai API access.