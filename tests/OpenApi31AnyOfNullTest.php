<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OpenAPI 3.1 nullable pattern using `type: null` inside anyOf/oneOf,
 * as opposed to the OpenAPI 3.0 `nullable: true` keyword.
 */
class OpenApi31AnyOfNullTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31-anyof-null.json');
    }

    // --- anyOf with { type: null } ---

    public function testAnyOfWithNullTypeAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-anyof', 'get', 200);

        $this->assertTrue($responseBody->match(['category' => null]));
    }

    public function testAnyOfWithNullTypeAcceptsValidObject(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-anyof', 'get', 200);

        $this->assertTrue($responseBody->match(['category' => ['id' => 1, 'name' => 'Dogs']]));
    }

    public function testAnyOfWithNullTypeAcceptsObjectWithMissingOptionalFields(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-anyof', 'get', 200);

        $this->assertTrue($responseBody->match(['category' => ['id' => 42]]));
    }

    public function testAnyOfWithNullTypeRejectsWrongType(): void
    {
        $this->expectException(NotMatchedException::class);

        $responseBody = $this->schema->getResponseParameters('/test-anyof', 'get', 200);

        $responseBody->match(['category' => 'not-an-object']);
    }

    // --- oneOf with { type: null } ---

    public function testOneOfWithNullTypeAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $this->assertTrue($responseBody->match(['tag' => null]));
    }

    public function testOneOfWithNullTypeAcceptsValidObject(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $this->assertTrue($responseBody->match(['tag' => ['label' => 'sale']]));
    }

    public function testOneOfWithNullTypeAcceptsObjectWithMissingOptionalFields(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $this->assertTrue($responseBody->match(['tag' => []]));
    }

    public function testOneOfWithNullTypeRejectsWrongType(): void
    {
        $this->expectException(NotMatchedException::class);

        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $responseBody->match(['tag' => 12345]);
    }
}