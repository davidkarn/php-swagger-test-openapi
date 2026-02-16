<?php

namespace ByJG\ApiTools;

use ByJG\ApiTools\Base\Schema;
use ByJG\ApiTools\Exception\NotMatchedException;
use PHPUnit\Framework\TestCase;

class OpenApi31ConstTest extends TestCase
{
    public function testConstStringValue(): void
    {
        $data = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => [
                                                    'const' => 'success'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $schema = Schema::fromArray($data);
        $responseBody = $schema->getResponseParameters('/test', 'get', 200);

        $validBody = ['status' => 'success'];
        $this->assertTrue($responseBody->match($validBody));
    }

    public function testConstValueRejectsWrongValue(): void
    {
        $this->expectException(NotMatchedException::class);
        $this->expectExceptionMessage('does not match const value');

        $data = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'status' => [
                                                    'const' => 'success'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $schema = Schema::fromArray($data);
        $responseBody = $schema->getResponseParameters('/test', 'get', 200);

        $invalidBody = ['status' => 'failure'];  // Should fail
        $responseBody->match($invalidBody);
    }

    public function testConstNumberValue(): void
    {
        $data = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'version' => [
                                                    'const' => 1
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $schema = Schema::fromArray($data);
        $responseBody = $schema->getResponseParameters('/test', 'get', 200);

        $validBody = ['version' => 1];
        $this->assertTrue($responseBody->match($validBody));
    }

    public function testConstBooleanValue(): void
    {
        $data = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'active' => [
                                                    'const' => true
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $schema = Schema::fromArray($data);
        $responseBody = $schema->getResponseParameters('/test', 'get', 200);

        $validBody = ['active' => true];
        $this->assertTrue($responseBody->match($validBody));
    }

    public function testConstInWebhook(): void
    {
        $schema = Schema::fromFile(__DIR__ . '/example/openapi31-webhooks.json');
        $requestBody = $schema->getWebhookRequestParameters('newUser', 'post');

        $validBody = [
            'userId' => 123,
            'event' => 'user.created',  // const value
            'timestamp' => '2024-01-01T00:00:00Z'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testConstInWebhookRejectsWrongValue(): void
    {
        $this->expectException(NotMatchedException::class);

        $schema = Schema::fromFile(__DIR__ . '/example/openapi31-webhooks.json');
        $requestBody = $schema->getWebhookRequestParameters('newUser', 'post');

        $invalidBody = [
            'userId' => 123,
            'event' => 'user.updated',  // Wrong const value
            'timestamp' => '2024-01-01T00:00:00Z'
        ];

        $requestBody->match($invalidBody);
    }
}
