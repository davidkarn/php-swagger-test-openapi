<?php

namespace ByJG\ApiTools\Base;

use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\GenericApiException;
use ByJG\ApiTools\Exception\InvalidDefinitionException;
use ByJG\ApiTools\Exception\InvalidRequestException;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;

abstract class Body
{
    const ERROR_NOTMATCHED = 0;
    const ERROR_GENERIC = 1;
    const ERROR_INVALIDREQUEST = 2;
    
    const SWAGGER_OBJECT = "object";
    const SWAGGER_ARRAY = "array";
    const SWAGGER_PROPERTIES = "properties";
    const SWAGGER_ADDITIONAL_PROPERTIES = "additionalProperties";
    const SWAGGER_REQUIRED = "required";

    /**
     * @var Schema
     */
    protected Schema $schema;

    /**
     * @var array
     */
    protected array $structure;

    /**
     * @var string
     */
    protected mixed $name;

    /**
     * OpenApi 2.0 does not describe null values, so this flag defines,
     * if match is ok when one of property, which has type, is null
     *
     * @var bool
     */
    protected bool $allowNullValues;

    /**
     * Body constructor.
     *
     * @param Schema $schema
     * @param mixed $name
     * @param array $structure
     * @param bool $allowNullValues
     */
    public function __construct(Schema $schema, mixed $name, array $structure, bool $allowNullValues = false)
    {
        $this->schema = $schema;
        $this->name = $name;
        $this->structure = $structure;
        $this->allowNullValues = $allowNullValues;
    }

    /**
     * @param mixed $body
     * @return bool
     *@throws GenericApiException
     * @throws InvalidDefinitionException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     * @throws RequiredArgumentNotFound
     * @throws DefinitionNotFoundException
     */
    abstract public function match(mixed $body): bool;

    protected function buildFailure(?string $message, array $schema, mixed $body, ?string $type, mixed $forKey, int $errorCode): array {
        return [
            'match'   => false,
            'message' => $message,
            'type'    => $type,
            'body'    => $body,
            'schema'  => $schema,
            'forKey'  => $forKey,
            'code'    => $errorCode
        ];        
    }

    protected function buildSuccess(?array $schema, mixed $body, ?string $type, mixed $forKey) {
        return [
            'match'   => true,
            'type'    => $type,
            'body'    => $body,
            'schema'  => $schema,
            'forKey'  => $forKey
        ];        
    }
        
    protected function matchString(mixed $name, array $schemaArray, mixed $body, string $type): array
    {
        if (isset($schemaArray['enum']) && !in_array($body, $schemaArray['enum'])) {
            return $this->buildFailure(
                "Value '$body' in '$name' not matched in ENUM. ", $schemaArray, $body, $type,
                $name, self::ERROR_NOTMATCHED
            );
        }
        else if (isset($schemaArray['pattern'])
                 && !$this->checkPattern($name, $body, $schemaArray['pattern'])) {
            return $this->buildFailure(
                "Value '$body' in '$name' not matched in pattern. ", $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
            );
        }
        else if (!is_string($body)) {
            return $this->buildFailure(
                "Value '" . var_export($body, true) . "' in '$name' is not string. ",
                $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
            );
        }
        else {
            return $this->buildSuccess($schemaArray, $body, $type, $name);
        }
    }

    private function checkPattern(mixed $name, mixed $body, string $pattern): bool
    {
        $pattern = '/' . rtrim(ltrim($pattern, '/'), '/') . '/';
        return (bool)preg_match($pattern, (string)$body);
    }

    protected function matchFile(mixed $name, array $schemaArray, mixed $body, string $type): array
    {
        return $this->buildSuccess($schemaArray, $body, $type, $name);
    }

    protected function matchNumber(mixed $name, array $schemaArray, mixed $body, string $type): array
    {
        if (!is_numeric($body)) {
            if (is_null($body)) {
                $body = 'null';
            }

            return $this->buildFailure(
                "Expected '$name' to be numeric, but found '$body'. ",
                $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
            );
        }
        else if (isset($schemaArray['pattern'])
                 && !$this->checkPattern($name, $body, $schemaArray['pattern'])) {
            return $this->buildFailure(
                "Value '$body' in '$name' not matched in pattern. ",
                $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
            );
        }
        else {
            return $this->buildSuccess($schemaArray, $body, $type, $name);
        }
    }
    
    protected function matchBool(mixed $name, array $schemaArray, mixed $body, string $type): array
    {
        if (!is_bool($body)) {
            if (is_null($body)) {
                $body = 'null';
            }
            
            return $this->buildFailure(
                "Expected '$name' to be boolean, but found '$body'. ",
                $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
            );
        }
        else {
            return $this->buildSuccess($schemaArray, $body, $type, $name);
        }
    }

    protected function matchArray(mixed $name, array $schemaArray, mixed $body, string $type): array
    {
        $failureCount = 0;
        $subItems     = [];
        $failureCode  = null;
        foreach ((array)$body as $index => $item) {
            if (!isset($schemaArray['items'])) {
                // If there is no type , there is no test.

                $subItems[$index] = $this->buildSuccess(
                    null, $item, null, $index
                );
            }
            else {
                $result = $this->matchInnerSchema($index, $schemaArray['items'], $item);
                
                if (!$result['match']) {
                    $failureCount++;
                    $failureCode = $failureCode ?? $result['code'];
                }

                $subItems[$index] = $result;
            }
        }

        if ($failureCount > 0) {
            return array_replace(
                $this->buildFailure(
                    null, $schemaArray, $body, $type, $name, $failureCode
                ),
                ['subItems' => $subItems]
            );
        }
        else {
            return array_replace(
                $this->buildSuccess($schemaArray, $body, $type, $name),
                ['subItems' => $subItems]
            );
        }
    }

    protected function matchTypes(mixed $name, mixed $schemaArray, mixed $body): array
    {
        if (!isset($schemaArray['type'])) {
            if (isset($schemaArray['items'])) {
                $schemaArray['type'] = 'array';
            }
            else {
                $schemaArray['type'] = 'object';
            }
        }
        
        $type = $schemaArray['type'];
        $nullable = match(true) {
            isset($schemaArray['nullable'])            => (bool)$schemaArray['nullable'],
            $type === 'null'                           => true,
            is_array($type) && in_array('null', $type) => true,
            true                                       => $this->schema->isAllowNullValues()
        };

        $validators = [
            self::SWAGGER_ARRAY => 'matchArray',
            'bool'              => 'matchBool',
            'boolean'           => 'matchBool',
            'integer'           => 'matchNumber',
            'float'             => 'matchNumber',
            'number'            => 'matchNumber',
            'file'              => 'matchFile',
            'string'            => 'matchString',
            'null'              => 'matchNull',
            'object'            => 'matchObject'
        ];

        $types = is_array($schemaArray['type']) ? $schemaArray['type'] : [$schemaArray['type']];
        if ($nullable && !in_array('null', $types)) {
            $types[] = 'null';
        }

        $failures = [];
        foreach ($types as $type) {
            $validator = $validators[$type];

            if ($validator) {
                $result = call_user_func(
                    [$this, $validator],
                    $name, $schemaArray, $body, $type
                );
                
                if ($result['match'] == true) {
                    return array_replace($result, ['schema' => $schemaArray, 'type' => $type]);
                }
                else {
                    $failures[] = $result;
                }
            }
            else {
                $failures[] = $this->buildFailure(
                    "Not all cases are defined. Please open an issue about this.",
                    $schemaArray, $body, $type, $name, self::ERROR_GENERIC
                );
            }
        }

        
        if (count($failures) > 1) {
            return [
                'message'        => null,
                'code'           => $failures[0]['code'],
                'match'          => false,
                'schema'         => $schemaArray,
                'multipleFailed' => true,
                'failedItems'    => $failures
            ];
        }
        else {
            return $failures[0];
        }
    }

    protected function matchObject(mixed $name, mixed $schemaArray, mixed $body, string $type): array
    {
        if ($body instanceof \SimpleXMLElement) {
            $encoded = json_encode($body);
            $body = json_decode($encoded !== false ? $encoded : '{}', true);
        }

        if (!is_array($body)) {
            return $this->buildFailure(
                "The body '" . $body . "' cannot be compared with the expected type " . $name,
                $schemaArray, $body, $type, $name, self::ERROR_INVALIDREQUEST
            );
        }
        else {
            $failureCount = 0;
            $failedItems = [];
            $subItems = [];

            if (
                empty($schemaArray[self::SWAGGER_PROPERTIES])
                && !isset($schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES])
            ) {
                $schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES] = true;
            }
            
            if (!isset($schemaArray[self::SWAGGER_REQUIRED])) {
                $schemaArray[self::SWAGGER_REQUIRED] = [];
            }

            $additionalProps = $schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES] ?? null;

            $schemaArray[self::SWAGGER_REQUIRED] =
                array_unique($schemaArray[self::SWAGGER_REQUIRED]);

            $props = $schemaArray[self::SWAGGER_PROPERTIES] ?? [];
            
            foreach ($body as $key => $value) {
                $def = match(true) {
                    isset($props[$key])        => $props[$key],
                    is_array($additionalProps) => $additionalProps,
                    true                       => null
                };

                if ($def === null) {
                    $subItems[$key] = $this->buildSuccess(
                        null, $value, null, $key
                    );
                }
                else {
                    $result = $this->matchInnerSchema($key, $def, $value, $key);
                    
                    if (!$result['match']) {
                        $failureCount++;
                        $failedItems[] = $result;
                    }
                    
                    $subItems[$key] = $result;
                }
            }

            if (count($diff = array_diff(
                $schemaArray[self::SWAGGER_REQUIRED],
                array_keys($subItems))
            )) {
                return $this->buildFailure(
                    count($diff) === 1
                        ? "The required property '".array_values($diff[0])."' does not exist in the body."
                        : "The required properties '".implode("', '", $diff)."' do not exist in the body.",
                    $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
                );
            }
            else if (
                !isset($schemaArray[self::SWAGGER_ADDITIONAL_PROPERTIES])
                    && count($diff = array_diff(
                        array_keys($subItems),
                        array_keys($schemaArray[self::SWAGGER_PROPERTIES]))) > 0
            ) {
                return $this->buildFailure(
                    count($diff) === 1
                        ? "The property '".array_values($diff)[0]."' has not been defined in '$name'"
                        : "The properties '".implode("', '", $diff)."' have not been defined in '$name'",
                    $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
                );
            }
            else if ($failureCount > 0) {
                return array_replace(
                    $this->buildFailure(null, $schemaArray, $body, $type, $name, $failedItems[0]['code']),
                    ['subItems' => $subItems]
                );
            }
            else {
                return array_replace(
                    $this->buildSuccess($schemaArray, $body, $type, $name),
                    ['subItems' => $subItems]
                );
            }
        }
    }

    /**
     * @param mixed $name
     * @param mixed $schemaArray
     * @param mixed $body
     * @return ?bool
     * @throws DefinitionNotFoundException
     * @throws InvalidDefinitionException
     * @throws GenericApiException
     * @throws InvalidRequestException
     * @throws NotMatchedException
     */
    protected function matchSchema(mixed $name, mixed $schemaArray, mixed $body): ?bool
    {
        $result = $this->matchInnerSchema($name, $schemaArray, $body);

        if (!$result['match']) {
            if ($result['code'] === self::ERROR_NOTMATCHED) {
                throw new NotMatchedException(
                    $this->getErrorMessage($result), $this->structure, result: $result
                );
            }
            else if ($result['code'] === self::ERROR_INVALIDREQUEST) {
                throw new InvalidRequestException(
                    $this->getErrorMessage($result), $this->structure, result: $result
                );
            }
            else if ($result['code'] === self::ERROR_GENERIC) {
                throw new GenericApiException(
                    $this->getErrorMessage($result), $this->structure, result: $result
                );
            }
        }
        else {
            return $result['match'];
        }
    }

    protected function getErrorMessage(array $result): ?string {
        if (!$result['match'] && !empty($result['message'])) {
            return $result['message'];
        }
        else {
            $items = $result['failedItems'] ?? $result['subItems'] ?? $result['failures'] ?? [];
            
            foreach ($items as $item) {
                if ($message = $this->getErrorMessage($item)) {
                    return $message;
                }
            }
            
            return null;
        }
    }
    
    protected function matchInnerSchema(mixed $name, mixed $schemaArray, mixed $body): array {
        if (!isset($schemaArray['$ref'])
            && isset($schemaArray['content'])
            && is_array($schemaArray['content'])
        ) {
            $contentKey = key($schemaArray['content']);

            if ($contentKey !== null && isset($schemaArray['content'][$contentKey]['schema'])) {
                $schemaArray = $schemaArray['content'][$contentKey]['schema'];
            }
        }

        if (isset($schemaArray['$ref']) && !is_array($schemaArray['$ref'])) {
            $definition = $this->schema->getDefinition($schemaArray['$ref']);
            return $this->matchInnerSchema($schemaArray['$ref'], $definition, $body, $name);
        }
        else if (isset($schemaArray['allOf'])) {
            $failedItems  = [];
            $parentSchema = array_diff_key($schemaArray, array_flip(['allOf']));

            return $this->matchInnerSchema(
                $name, array_merge_recursive($parentSchema, $schemaArray['allOf']), $body, $name
            );
        }
        else if (isset($schemaArray['oneOf'])) {
            $failedItems  = [];
            $parentSchema = array_diff_key($schemaArray, array_flip(['oneOf']));

            foreach ($schemaArray['oneOf'] as $schema) {
                $result = $this->matchInnerSchema(
                    $name, array_replace($parentSchema, $schema), $body
                );

                if (!$result['match']) {
                    $failedItems[] = $result;
                }
                else {
                    return $result;
                }
            }

            return array_replace(
                $this->buildFailure(
                    $failedItems." failure(s) in oneOf schema",
                    $schemaArray, $body, null, $name, $failedItems[0]['code']
                ),
                ['failedItems' => $failedItems]
            );
        }
        else if ($schemaArray === []) {
            /**
             * OpenApi 2.0 does not describe ANY object value
             * But there is hack that makes ANY object possible, described in link below
             * To make that hack works, we need such condition
             * @link https://stackoverflow.com/questions/32841298/swagger-2-0-what-schema-to-accept-any-complex-json-value
             */
            return $this->buildSuccess($schemaArray, $body, 'object', $name);
        }
        else {
            return $this->matchTypes($name, $schemaArray, $body, $name);
        }
    }

    protected function matchNull(mixed $name, array $schemaArray, mixed $body, string $type): array
    {
        if (!is_null($body)) {
            return $this->buildFailure(
                "Expected '$name' to be null, but found ".var_export($body, true),
                $schemaArray, $body, $type, $name, self::ERROR_NOTMATCHED
            );
        }
        else {
            return $this->buildSuccess($schemaArray, $body, $type, $name);
        }
    }
}
