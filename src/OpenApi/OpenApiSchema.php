<?php

namespace ByJG\ApiTools\OpenApi;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\InvalidRequestException;

/**
 * OpenAPI 3.0.x Schema implementation
 *
 * Handles OpenAPI 3.0.0, 3.0.1, 3.0.2, and 3.0.3 specifications.
 */
class OpenApiSchema extends OpenApiBase
{
    /**
     * @inheritDoc
     */
    #[\Override]
    protected function getDefaultVersion(array $data): string
    {
        return $data['openapi'] ?? '3.0';
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function getServerVariableDefault(array $variableDefinition): string
    {
        // OpenAPI 3.0 requires default
        return $variableDefinition['default'];
    }

    /**
     * @inheritDoc
     * @throws InvalidRequestException
     */
    #[\Override]
    public function getRequestParameters(string $path, string $method): Body
    {
        $structure = $this->getPathDefinition($path, $method);

        if (!isset($structure['requestBody'])) {
            return new OpenApiRequestBody($this, "$method $path", []);
        }
        return new OpenApiRequestBody($this, "$method $path", $structure['requestBody']);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getResponseBody(Schema $schema, string $name, array $structure, bool $allowNullValues = false): Body
    {
        return new OpenApiResponseBody($schema, $name, $structure, $allowNullValues);
    }
}
