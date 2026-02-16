<?php

namespace ByJG;

use ByJG\ApiTools\Base\Schema;
use PHPUnit\Framework\TestCase;

class OpenApi31RefSiblingTest extends TestCase
{
    public function testRefWithSiblingKeywords(): void
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
                                            '$ref' => '#/components/schemas/User',
                                            'description' => 'User object with extra description'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ];

        $schema = Schema::fromArray($data);
        $responseBody = $schema->getResponseParameters('/test', 'get', 200);

        $validBody = ['id' => 1, 'name' => 'John'];

        $this->assertTrue($responseBody->match($validBody));
    }

    public function testRefWithSiblingProperties(): void
    {
        $data = [
            'openapi' => '3.1.0',
            'info' => ['title' => 'Test', 'version' => '1.0.0'],
            'paths' => [
                '/test' => [
                    'post' => [
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/BaseUser',
                                        'title' => 'Extended User'
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created'
                            ]
                        ]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'BaseUser' => [
                        'type' => 'object',
                        'properties' => [
                            'username' => ['type' => 'string'],
                            'email' => ['type' => 'string']
                        ],
                        'required' => ['username']
                    ]
                ]
            ]
        ];

        $schema = Schema::fromArray($data);
        $requestBody = $schema->getRequestParameters('/test', 'post');

        $validBody = [
            'username' => 'johndoe',
            'email' => 'john@example.com'
        ];

        $this->assertTrue($requestBody->match($validBody));
    }

    public function testOpenApi30DoesNotSupportSiblingKeywords(): void
    {
        // OpenAPI 3.0 should still work with traditional $ref (no siblings)
        $data = [
            'openapi' => '3.0.3',
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
                                            '$ref' => '#/components/schemas/User'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer']
                        ]
                    ]
                ]
            ]
        ];

        $schema = Schema::fromArray($data);
        $responseBody = $schema->getResponseParameters('/test', 'get', 200);

        $this->assertTrue($responseBody->match(['id' => 1]));
    }
}
