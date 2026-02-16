<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31ConditionalTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31-conditional.json');
    }

    public function testUSPostalCodePattern(): void
    {
        $requestBody = $this->schema->getRequestParameters('/shipping', 'post');

        $validBody = [
            'country' => 'US',
            'postalCode' => '12345'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testUSPostalCodeWithExtension(): void
    {
        $requestBody = $this->schema->getRequestParameters('/shipping', 'post');

        $validBody = [
            'country' => 'US',
            'postalCode' => '12345-6789'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testUSPostalCodeInvalidFormat(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/shipping', 'post');

        $invalidBody = [
            'country' => 'US',
            'postalCode' => 'ABC123'  // Invalid for US
        ];

        $requestBody->match($invalidBody);
    }

    public function testNonUSPostalCode(): void
    {
        $requestBody = $this->schema->getRequestParameters('/shipping', 'post');

        $validBody = [
            'country' => 'CA',
            'postalCode' => 'K1A 0B1'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testNonUSPostalCodeWithDifferentFormat(): void
    {
        $requestBody = $this->schema->getRequestParameters('/shipping', 'post');

        $validBody = [
            'country' => 'UK',
            'postalCode' => 'SW1A 1AA'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testConditionalWithRequiredField(): void
    {
        $requestBody = $this->schema->getRequestParameters('/product', 'post');

        $validBody = [
            'productType' => 'subscription',
            'price' => 9.99,
            'subscription' => [
                'interval' => 'monthly'
            ]
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testConditionalWithRequiredFieldMissing(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/product', 'post');

        $invalidBody = [
            'productType' => 'subscription',
            'price' => 9.99
            // Missing required 'subscription' field when productType is 'subscription'
        ];

        $requestBody->match($invalidBody);
    }

    public function testConditionalNotMetNoRequirement(): void
    {
        $requestBody = $this->schema->getRequestParameters('/product', 'post');

        $validBody = [
            'productType' => 'one-time',
            'price' => 49.99
            // subscription field not required when productType is not 'subscription'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }
}
