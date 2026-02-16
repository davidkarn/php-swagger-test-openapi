<?php

namespace Tests;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31TupleTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31-tuples.json');
    }

    public function testValidCoordinateTuple(): void
    {
        $requestBody = $this->schema->getRequestParameters('/coordinates', 'post');

        $validBody = [
            'location' => [40.7128, -74.0060]  // Latitude, Longitude
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testCoordinateTupleWithWrongType(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/coordinates', 'post');

        $invalidBody = [
            'location' => ['invalid', -74.0060]  // First element is non-numeric string
        ];

        $requestBody->match($invalidBody);
    }

    public function testCoordinateTupleTooFewItems(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/coordinates', 'post');

        $invalidBody = [
            'location' => [40.7128]  // Missing longitude
        ];

        $requestBody->match($invalidBody);
    }

    public function testCoordinateTupleTooManyItems(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/coordinates', 'post');

        $invalidBody = [
            'location' => [40.7128, -74.0060, 100]  // Extra element
        ];

        $requestBody->match($invalidBody);
    }

    public function testValidNameTuple(): void
    {
        $requestBody = $this->schema->getRequestParameters('/person', 'post');

        $validBody = [
            'name' => ['John', 'Doe']
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testNameTupleWithMiddleName(): void
    {
        $requestBody = $this->schema->getRequestParameters('/person', 'post');

        $validBody = [
            'name' => ['John', 'Michael', 'Doe']  // maxItems is 3
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testNameTupleTooManyItems(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/person', 'post');

        $invalidBody = [
            'name' => ['John', 'Michael', 'James', 'Doe']  // Too many names
        ];

        $requestBody->match($invalidBody);
    }

    public function testValidRGBTuple(): void
    {
        $requestBody = $this->schema->getRequestParameters('/rgb', 'post');

        $validBody = [
            'color' => [255, 128, 64]  // RGB values
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testRGBTupleWithWrongType(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/rgb', 'post');

        $invalidBody = [
            'color' => [255, 'invalid', 64]  // Second value is non-numeric string
        ];

        $requestBody->match($invalidBody);
    }

    public function testRGBTupleTooFewItems(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getRequestParameters('/rgb', 'post');

        $invalidBody = [
            'color' => [255, 128]  // Missing blue
        ];

        $requestBody->match($invalidBody);
    }
}
