<?php

/**
 * Script to generate Valinor-compatible DTOs from OpenAPI specification
 */
$openApiSpec = file_get_contents(__DIR__.'/openapi.json');
$spec = json_decode($openApiSpec, true);

if (! $spec) {
    echo "Failed to parse OpenAPI specification\n";
    exit(1);
}

$outputDir = __DIR__.'/src/Generated/Dto';
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Define the models we want to generate based on our API usage
$modelsToGenerate = [
    'Team' => 'TeamResponse',
    'User' => 'AdministratorResponse',
    'PrivateAIKey' => 'LlmKeysResponse', // for LLM keys
    'LiteLLMToken' => 'VdbKeysResponse',  // for VDB keys
    'Region' => 'RegionResponse',
    'APIToken' => 'APIToken',
];

function phpType($propSchema, $isNullable = false): string
{
    // Handle anyOf (nullable) types
    if (isset($propSchema['anyOf'])) {
        $nonNullVariant = null;
        foreach ($propSchema['anyOf'] as $variant) {
            if (isset($variant['type']) && $variant['type'] !== 'null') {
                $nonNullVariant = $variant;
                break;
            }
        }
        if ($nonNullVariant) {
            $type = phpType($nonNullVariant, false);

            return "?{$type}"; // anyOf always makes the field nullable
        }

        return '?mixed'; // fallback
    }

    $openApiType = $propSchema['type'] ?? 'mixed';
    $format = $propSchema['format'] ?? null;

    $type = match ($openApiType) {
        // 'string' => $format === 'date-time' ? '\DateTimeInterface' : 'string',
        'string' => 'string',
        'integer' => 'int',
        'number' => 'float',
        'boolean' => 'bool',
        'array' => 'array',
        'object' => 'array', // Use array for generic objects
        default => 'mixed'
    };

    return $isNullable ? "?{$type}" : $type;
}

function generateDto(string $modelName, array $schema, string $className): string
{
    $properties = $schema['properties'] ?? [];
    $required = $schema['required'] ?? [];

    // Separate required and optional parameters to avoid PHP 8+ deprecation warnings
    $requiredParams = [];
    $optionalParams = [];

    foreach ($properties as $propName => $propSchema) {
        $isRequired = in_array($propName, $required);
        $isNullable = isset($propSchema['anyOf']) || ! $isRequired;

        $type = phpType($propSchema, $isNullable && ! isset($propSchema['anyOf']));

        $description = $propSchema['title'] ?? $propSchema['description'] ?? '';
        $docComment = $description ? "        /**\n         * {$description}\n         */\n" : '';

        if ($isRequired) {
            $requiredParams[] = "{$docComment}        public {$type} \${$propName}";
        } else {
            $optionalParams[] = "{$docComment}        public {$type} \${$propName} = null";
        }
    }

    // Combine required parameters first, then optional ones
    $allParams = array_merge($requiredParams, $optionalParams);
    $constructorParams = implode(",\n", $allParams);

    return <<<PHP
<?php

namespace Amazeeio\\PolydockAppAmazeeioPrivateGpt\\Generated\\Dto;

/**
 * {$className}
 *
 * Auto-generated from OpenAPI specification. Do not edit manually.
 * @see https://api.amazee.ai/openapi.json
 */
final readonly class {$className}
{
    public function __construct(
{$constructorParams}
    ) {}
}
PHP;
}

// Generate health response (special case - no schema defined)
$healthDto = <<<'PHP'
<?php

namespace Amazeeio\PolydockAppAmazeeioPrivateGpt\Generated\Dto;

/**
 * HealthResponse
 *
 * Auto-generated from OpenAPI specification. Do not edit manually.
 * @see https://api.amazee.ai/openapi.json
 */
final readonly class HealthResponse
{
    public function __construct(
        /**
         * Health status
         */
        public string $status
    ) {}
}
PHP;

file_put_contents($outputDir.'/HealthResponse.php', $healthDto);
echo "Generated HealthResponse.php\n";

// Generate DTOs for each model
foreach ($modelsToGenerate as $modelName => $className) {
    if (! isset($spec['components']['schemas'][$modelName])) {
        echo "Warning: Model '{$modelName}' not found in OpenAPI spec\n";

        continue;
    }

    $schema = $spec['components']['schemas'][$modelName];
    $dto = generateDto($modelName, $schema, $className);

    $filename = $outputDir."/{$className}.php";
    file_put_contents($filename, $dto);
    echo "Generated {$className}.php\n";
}

echo "DTO generation complete!\n";
