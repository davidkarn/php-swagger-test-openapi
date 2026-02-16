<?php

namespace ByJG;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31ResponseBodyTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');
    }

    public function testResponseBodyMatchValid(): void
    {
        $responseBody = $this->schema->getResponseParameters('/users', 'get', 200);

        $validBody = [
            'users' => [
                ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
            ]
        ];

        $this->assertTrue($responseBody->match($validBody));
    }

    // Note: Nullable type array tests will be added in Phase 2
    // public function testResponseBodyMatchWithNullEmail(): void

    public function testResponseBodyMatchEmptyArray(): void
    {
        $responseBody = $this->schema->getResponseParameters('/users', 'get', 200);

        $validBody = [
            'users' => []  // Empty array is valid
        ];

        $this->assertTrue($responseBody->match($validBody));
    }

    public function testResponseBodyMatchInvalidStructure(): void
    {
        $this->expectException(NotMatchedException::class);

        $responseBody = $this->schema->getResponseParameters('/users', 'get', 200);

        $invalidBody = [
            'users' => [
                ['id' => 1, 'name' => 'John'],  // Missing required fields is OK if not in required array
                ['id' => 'invalid', 'name' => 'Jane']  // Invalid type for id
            ]
        ];

        $responseBody->match($invalidBody);
    }

    public function testCreatedResponse(): void
    {
        $responseBody = $this->schema->getResponseParameters('/users', 'post', 201);

        $validBody = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $this->assertTrue($responseBody->match($validBody));
    }
}
