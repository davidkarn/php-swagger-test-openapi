<?php

namespace ByJG\ApiTools\OpenApi31;

use ByJG\ApiTools\Base\Body;
use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\Util\Uri;

class OpenApi31Schema extends Schema
{
    protected array $serverVariables = [];

    /**
     * Initialize with schema data, which can be a PHP array or encoded as JSON.
     *
     * @param array|string $data
     */
    public function __construct(array|string $data)
    {
        // when given a string, decode from JSON
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->jsonFile = $data;
        $this->specificationVersion = $data['openapi'] ?? '3.1';
    }

    #[\Override]
    public function getServerUrl(): string
    {
        if (!isset($this->jsonFile['servers'])) {
            return '';
        }
        $serverUrl = $this->jsonFile['servers'][0]['url'];

        if (isset($this->jsonFile['servers'][0]['variables'])) {
            foreach ($this->jsonFile['servers'][0]['variables'] as $var => $value) {
                if (!isset($this->serverVariables[$var])) {
                    // OpenAPI 3.1: default is optional
                    $this->serverVariables[$var] = $value['default'] ?? '';
                }
            }
        }

        foreach ($this->serverVariables as $var => $value) {
            $replaced = preg_replace("/\{$var}/", $value, $serverUrl);
            $serverUrl = is_string($replaced) ? $replaced : $serverUrl;
        }

        return $serverUrl;
    }

    #[\Override]
    public function getBasePath(): string
    {
        $uriServer = new Uri($this->getServerUrl());
        return $uriServer->getPath();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function validateArguments(string $parameterIn, array $parameters, array $arguments): void
    {
        foreach ($parameters as $parameter) {
            if (isset($parameter['$ref'])) {
                $paramParts = explode("/", $parameter['$ref']);
                if (count($paramParts) != 4 || $paramParts[0] != "#" || $paramParts[1] != self::SWAGGER_COMPONENTS || $paramParts[2] != self::SWAGGER_PARAMETERS) {
                    throw new InvalidDefinitionException(
                        "Not get the reference in the expected format #/components/parameters/<NAME>"
                    );
                }
                if (!isset($this->jsonFile[self::SWAGGER_COMPONENTS][self::SWAGGER_PARAMETERS][$paramParts[3]])) {
                    throw new DefinitionNotFoundException(
                        "Not find reference #/components/parameters/$paramParts[3]"
                    );
                }
                $parameter = $this->jsonFile[self::SWAGGER_COMPONENTS][self::SWAGGER_PARAMETERS][$paramParts[3]];
            }
            if ($parameter['in'] === $parameterIn &&
                $parameter['schema']['type'] === "integer"
                && filter_var($arguments[$parameter['name']], FILTER_VALIDATE_INT) === false) {
                throw new NotMatchedException('Path expected an integer value');
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     */
    #[\Override]
    public function getDefinition(string $name): mixed
    {
        $nameParts = explode('/', $name);

        if (count($nameParts) < 4 || $nameParts[0] !== '#') {
            throw new InvalidDefinitionException('Invalid Component');
        }

        if (!isset($this->jsonFile[$nameParts[1]][$nameParts[2]][$nameParts[3]])) {
            throw new DefinitionNotFoundException("Component'$name' not found");
        }

        return $this->jsonFile[$nameParts[1]][$nameParts[2]][$nameParts[3]];
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
            return new OpenApi31RequestBody($this, "$method $path", []);
        }
        return new OpenApi31RequestBody($this, "$method $path", $structure['requestBody']);
    }

    public function setServerVariable(string $var, string $value): void
    {
        $this->serverVariables[$var] = $value;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getResponseBody(Schema $schema, string $name, array $structure, bool $allowNullValues = false): Body
    {
        return new OpenApi31ResponseBody($schema, $name, $structure, $allowNullValues);
    }

    /**
     * Get webhook definition for OpenAPI 3.1
     *
     * @param string $webhookName
     * @param string $method
     * @return mixed
     * @throws DefinitionNotFoundException
     */
    public function getWebhookDefinition(string $webhookName, string $method): mixed
    {
        if (!isset($this->jsonFile['webhooks'][$webhookName][$method])) {
            throw new DefinitionNotFoundException("Webhook '$webhookName' with method '$method' not found");
        }
        return $this->jsonFile['webhooks'][$webhookName][$method];
    }

    /**
     * Check if schema has webhooks
     *
     * @return bool
     */
    public function hasWebhooks(): bool
    {
        return isset($this->jsonFile['webhooks']) && !empty($this->jsonFile['webhooks']);
    }

    /**
     * Get all webhook names
     *
     * @return array
     */
    public function getWebhookNames(): array
    {
        if (!$this->hasWebhooks()) {
            return [];
        }
        return array_keys($this->jsonFile['webhooks']);
    }

    /**
     * Get webhook request parameters
     *
     * @param string $webhookName
     * @param string $method
     * @return Body
     * @throws DefinitionNotFoundException
     */
    public function getWebhookRequestParameters(string $webhookName, string $method): Body
    {
        $structure = $this->getWebhookDefinition($webhookName, $method);

        if (!isset($structure['requestBody'])) {
            return new OpenApi31RequestBody($this, "webhook $webhookName $method", []);
        }
        return new OpenApi31RequestBody($this, "webhook $webhookName $method", $structure['requestBody']);
    }

    /**
     * Get webhook response parameters
     *
     * @param string $webhookName
     * @param string $method
     * @param int $status
     * @return Body
     * @throws DefinitionNotFoundException
     */
    public function getWebhookResponseParameters(string $webhookName, string $method, int $status): Body
    {
        $structure = $this->getWebhookDefinition($webhookName, $method);

        if (!isset($structure['responses'][$status])) {
            throw new DefinitionNotFoundException(
                "Response status '$status' not found for webhook '$webhookName' method '$method'"
            );
        }

        return $this->getResponseBody(
            $this,
            "webhook $webhookName $method $status",
            $structure['responses'][$status]
        );
    }

    /**
     * Get the JSON Schema dialect used
     *
     * @return string|null
     */
    public function getSchemaDialect(): ?string
    {
        return $this->jsonFile['$schema'] ?? null;
    }

    /**
     * Check if schema uses JSON Schema 2020-12
     *
     * @return bool
     */
    public function isJsonSchema202012(): bool
    {
        $dialect = $this->getSchemaDialect();
        return $dialect === null || str_contains($dialect, '2020-12');
    }
}
