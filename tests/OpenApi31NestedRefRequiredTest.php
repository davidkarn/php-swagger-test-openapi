<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31NestedRefRequiredTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31-nested-ref-required.json');
    }

    public function testNestedRefPropertiesWithAllFields(): void
    {
        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp',
            'manager' => [
                'email' => 'john.doe@example.com',
                'phone' => '+1234567890',
                'firstName' => 'John',
                'middleName' => 'Q',
                'lastName' => 'Doe'
            ]
        ];

        $this->assertTrue($requestBody->match($body));
    }

    public function testNestedRefPropertiesWithRequiredFieldOnly(): void
    {
        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp',
            'manager' => [
                'phone' => '+1234567890'
            ]
        ];

        $this->assertTrue($requestBody->match($body));
    }

    public function testNestedRefPropertiesMissingRequiredField(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp',
            'manager' => [
                'email' => 'john.doe@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe'
                // Missing required 'phone' field
            ]
        ];

        $requestBody->match($body);
    }

    public function testNullableManagerAcceptsNull(): void
    {
        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp',
            'manager' => null
        ];

        $this->assertTrue($requestBody->match($body));
    }

    public function testNullableManagerCanBeOmitted(): void
    {
        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp'
        ];

        $this->assertTrue($requestBody->match($body));
    }

    public function testNestedRefEmailValidation(): void
    {
        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp',
            'manager' => [
                'phone' => '+1234567890',
                'email' => 'valid@example.com'
            ]
        ];

        $this->assertTrue($requestBody->match($body));
    }

    public function testNestedRefPhoneValidation(): void
    {
        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp',
            'manager' => [
                'phone' => '+12345678901234'
            ]
        ];

        $this->assertTrue($requestBody->match($body));
    }

    public function testNestedRefWithPartialFields(): void
    {
        $requestBody = $this->schema->getRequestParameters('/organization', 'post');

        $body = [
            'name' => 'ACME Corp',
            'manager' => [
                'phone' => '+1234567890',
                'firstName' => 'John',
                'lastName' => 'Doe'
            ]
        ];

        $this->assertTrue($requestBody->match($body));
    }
}