<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31RequestBodyTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');
    }

    public function testRequestBodyMatchValid(): void
    {
        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $validBody = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    // Note: Nullable type array tests will be added in Phase 2
    // public function testRequestBodyMatchNullableEmail(): void

    public function testRequestBodyMatchMissingOptionalField(): void
    {
        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $validBody = [
            'id' => 1,
            'name' => 'John Doe'
            // email is optional, so it can be omitted
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testRequestBodyMatchMissingRequiredField(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $invalidBody = [
            'id' => 1
            // Missing required 'name' field
        ];

        $requestBody->match($invalidBody);
    }

    public function testRequestBodyMatchInvalidType(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/users', 'post');

        $invalidBody = [
            'id' => 'not-a-number',  // Should be integer
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $requestBody->match($invalidBody);
    }
}
