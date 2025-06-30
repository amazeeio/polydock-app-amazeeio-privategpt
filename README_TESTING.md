# Testing Documentation

This document describes the comprehensive test suite for the polydock-app-amazeeio-privategpt package.

## Test Framework

This project uses **Pest PHP** as the primary testing framework, providing a modern and expressive testing experience.

## Test Structure

```
tests/
├── Pest.php                           # Pest configuration
├── TestCase.php                       # Base test case with common setup
├── Unit/                              # Unit tests
│   ├── AmazeeAiClientTest.php         # Test amazee.ai client
│   ├── PolydockPrivateGptAppTest.php  # Test main app class
│   └── Traits/                        # Test individual traits
│       └── UsesAmazeeAiDirectTest.php # Test amazee.ai integration trait
├── Integration/                       # Integration tests
│   └── FullWorkflowTest.php          # End-to-end workflow tests
└── Mocks/                            # Mock classes and fixtures
    ├── LagoonClientMock.php          # Mock Lagoon client
    ├── AmazeeAiClientMock.php        # Mock amazee.ai client
    └── MockResponses/                # JSON response fixtures
        ├── lagoon/                   # Lagoon API response examples
        └── amazeeai/                 # amazee.ai API response examples
```

## Running Tests

### Run All Tests
```bash
./vendor/bin/pest
```

### Run Specific Test Suites
```bash
# Unit tests only
./vendor/bin/pest tests/Unit

# Integration tests only
./vendor/bin/pest tests/Integration

# Specific test file
./vendor/bin/pest tests/Unit/AmazeeAiClientTest.php
```

### Run Tests with Coverage
```bash
./vendor/bin/pest --coverage
```

## Mock Services

### External Service Mocks

The test suite includes comprehensive mocks for both external services:

#### Lagoon API Mock (`LagoonClientMock`)
- Mocks all Lagoon client operations
- Provides realistic responses for project creation, deployment, etc.
- Includes failure scenarios for error testing

#### amazee.ai API Mock (`AmazeeAiClientMock`) 
- Mocks all amazee.ai client operations
- Simulates team creation, key generation, health checks
- Includes error scenarios and edge cases

### Mock Response Fixtures

Realistic JSON response fixtures are provided in `tests/Mocks/MockResponses/`:

- **Lagoon responses**: Project creation, deployment status, environment details
- **amazee.ai responses**: Team creation, LLM/VDB key generation, health checks

## Test Coverage Areas

### Unit Tests

#### AmazeeAiClient (18 tests)
- HTTP client initialization and configuration
- API endpoint interactions (teams, keys, health)
- Error handling and exception scenarios
- Response parsing and validation

#### PolydockPrivateGptApp (20 tests)
- App configuration and variable definitions
- Lagoon client integration and validation
- External service ping and health checks
- Project variable management
- Error handling and status flow

#### Traits (15 tests)
- amazee.ai direct integration trait
- Team and administrator management
- Key generation workflows
- Service health monitoring

### Integration Tests (12 tests)
- Full workflow scenarios
- Service integration patterns
- Configuration validation
- Error handling across services
- Mock service interactions

## Key Testing Patterns

### Pest Syntax
The test suite uses Pest's modern `describe()` and `it()` syntax for readable test organization:

```php
describe('AmazeeAiClient', function () {
    it('successfully creates a team', function () {
        // Test implementation
    });
});
```

### Mock Usage
Tests use Mockery for comprehensive mocking:

```php
$mockClient = AmazeeAiClientMock::create();
$result = $mockClient->createTeam('test-team', 'admin@example.com');
expect($result['id'])->toBe('team-123');
```

### Helper Methods
The base `TestCase` class provides helper methods for common mock setup:

```php
$appInstance = $this->createMockPolydockAppInstance([
    'lagoon-project-name' => 'test-project',
    'amazee-ai-api-key' => 'test-key'
]);
```

## Dependencies

### Testing Dependencies
- **pestphp/pest**: Modern PHP testing framework
- **mockery/mockery**: Advanced mocking capabilities
- **orchestra/testbench**: Laravel package testing support

### Mock Fixtures
- Realistic API response examples
- Error scenario simulations
- Edge case handling

## Benefits

1. **Comprehensive Coverage**: Tests cover all major functionality including external service integration
2. **Realistic Mocking**: Mock services provide realistic responses without external dependencies
3. **Error Scenarios**: Extensive testing of failure modes and error handling
4. **Modern Syntax**: Pest provides clean, readable test code
5. **Fast Execution**: Mocked external services enable rapid test execution
6. **Isolation**: Tests run independently without external service dependencies

## Future Enhancements

- Add more trait-specific tests for deployment workflows
- Implement performance testing for large-scale operations
- Add contract testing for external API compatibility
- Expand error scenario coverage
- Add mutation testing for test quality assessment