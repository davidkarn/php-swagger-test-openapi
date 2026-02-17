<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi30OneOfNullableTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi30-oneof-nullable.json');
    }

    // --- oneOf + nullable ---

    public function testOneOfNullableAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $this->assertTrue($responseBody->match(['category' => null]));
    }

    public function testOneOfNullableAcceptsValidObject(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $this->assertTrue($responseBody->match(['category' => ['id' => 1, 'name' => 'Dogs']]));
    }

    public function testOneOfNullableAcceptsObjectWithMissingOptionalFields(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $this->assertTrue($responseBody->match(['category' => ['id' => 5]]));
    }

    public function testOneOfNullableRejectsWrongType(): void
    {
        $this->expectException(NotMatchedException::class);

        $responseBody = $this->schema->getResponseParameters('/test-oneof', 'get', 200);

        $responseBody->match(['category' => 'not-an-object']);
    }

    // --- anyOf + nullable ---

    public function testAnyOfNullableAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-anyof', 'get', 200);

        $this->assertTrue($responseBody->match(['tag' => null]));
    }

    public function testAnyOfNullableAcceptsValidTagObject(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-anyof', 'get', 200);

        $this->assertTrue($responseBody->match(['tag' => ['label' => 'sale']]));
    }

    public function testAnyOfNullableAcceptsString(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-anyof', 'get', 200);

        $this->assertTrue($responseBody->match(['tag' => 'plain-string']));
    }

    // --- allOf + nullable ---

    public function testAllOfNullableAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-allof', 'get', 200);

        $this->assertTrue($responseBody->match(['product' => null]));
    }

    public function testAllOfNullableAcceptsValidObject(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-allof', 'get', 200);

        $this->assertTrue($responseBody->match(['product' => ['sku' => 'ABC-123']]));
    }

    public function testAllOfNullableRejectsWrongType(): void
    {
        $this->expectException(NotMatchedException::class);

        $responseBody = $this->schema->getResponseParameters('/test-allof', 'get', 200);

        $responseBody->match(['product' => 'not-an-object']);
    }
}