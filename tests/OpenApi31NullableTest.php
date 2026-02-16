<?php

namespace ByJG;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31NullableTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31-nullable.json');
    }

    public function testNullableStringAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableString' => null,
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testNullableStringAcceptsString(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableString' => 'value',
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testRegularStringRejectsNull(): void
    {
        $this->expectException(NotMatchedException::class);
        // Error message may vary based on nullable handling, just check exception is thrown

        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableString' => 'value',
            'regularString' => null  // Should fail
        ];

        $responseBody->match($body);
    }

    public function testNullableNumberAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableNumber' => null,
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testNullableNumberAcceptsNumber(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableNumber' => 42,
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testNullableObjectAcceptsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableObject' => null,
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testNullableObjectAcceptsObject(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableObject' => ['prop' => 'value'],
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testMixedNullableFields(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableString' => null,
            'nullableNumber' => 42,
            'nullableObject' => null,
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testAllFieldsNull(): void
    {
        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableString' => null,
            'nullableNumber' => null,
            'nullableObject' => null,
            'regularString' => 'test'
        ];

        $this->assertTrue($responseBody->match($body));
    }

    public function testNullableStringWithWrongType(): void
    {
        $this->expectException(NotMatchedException::class);

        $responseBody = $this->schema->getResponseParameters('/test-nullable', 'get', 200);

        $body = [
            'nullableString' => 123,  // Should be string or null, not number
            'regularString' => 'test'
        ];

        $responseBody->match($body);
    }
}
