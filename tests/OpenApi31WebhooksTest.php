<?php

namespace ByJG;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\DefinitionNotFoundException;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31WebhooksTest extends TestCase
{
    protected Schema $schema;

    protected function setUp(): void
    {
        $this->schema = Schema::fromFile(__DIR__ . '/example/openapi31-webhooks.json');
    }

    public function testHasWebhooks(): void
    {
        $this->assertTrue($this->schema->hasWebhooks());
    }

    public function testGetWebhookNames(): void
    {
        $names = $this->schema->getWebhookNames();

        $this->assertCount(2, $names);
        $this->assertContains('newUser', $names);
        $this->assertContains('orderUpdated', $names);
    }

    public function testGetWebhookDefinition(): void
    {
        $definition = $this->schema->getWebhookDefinition('newUser', 'post');

        $this->assertIsArray($definition);
        $this->assertEquals('New user webhook', $definition['summary']);
    }

    public function testWebhookRequestBodyValidation(): void
    {
        $requestBody = $this->schema->getWebhookRequestParameters('newUser', 'post');

        $validBody = [
            'userId' => 123,
            'event' => 'user.created',
            'timestamp' => '2024-01-01T00:00:00Z'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testWebhookRequestBodyConstValidation(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getWebhookRequestParameters('newUser', 'post');

        $invalidBody = [
            'userId' => 123,
            'event' => 'wrong.event',  // Should be 'user.created'
            'timestamp' => '2024-01-01T00:00:00Z'
        ];

        $requestBody->match($invalidBody);
    }

    public function testWebhookRequestBodyMissingRequired(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getWebhookRequestParameters('newUser', 'post');

        $invalidBody = [
            'userId' => 123
            // Missing required 'event' field
        ];

        $requestBody->match($invalidBody);
    }

    public function testWebhookWithReference(): void
    {
        $requestBody = $this->schema->getWebhookRequestParameters('orderUpdated', 'post');

        $validBody = [
            'orderId' => 456,
            'status' => 'completed'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testWebhookWithInvalidEnumValue(): void
    {
        $this->expectException(NotMatchedException::class);

        $requestBody = $this->schema->getWebhookRequestParameters('orderUpdated', 'post');

        $invalidBody = [
            'orderId' => 456,
            'status' => 'invalid-status'  // Not in enum
        ];

        $requestBody->match($invalidBody);
    }

    public function testWebhookResponseValidation(): void
    {
        $responseBody = $this->schema->getWebhookResponseParameters('newUser', 'post', 200);

        // 200 response has no body defined, so empty/null body should be valid
        $this->assertTrue($responseBody->match(null));
    }

    public function testNonExistentWebhookThrowsException(): void
    {
        $this->expectException(DefinitionNotFoundException::class);

        $this->schema->getWebhookDefinition('nonExistent', 'post');
    }

    public function testSchemaWithoutWebhooks(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31.json');

        $this->assertFalse($schema->hasWebhooks());
        $this->assertEmpty($schema->getWebhookNames());
    }

    public function testWebhookWithAllValidStatuses(): void
    {
        $requestBody = $this->schema->getWebhookRequestParameters('orderUpdated', 'post');

        $statuses = ['pending', 'processing', 'completed', 'cancelled'];

        foreach ($statuses as $status) {
            $validBody = [
                'orderId' => 123,
                'status' => $status
            ];
            $this->assertTrue($requestBody->match($validBody));
        }
    }
}
