---
sidebar_position: 12
---

# OpenAPI Version Comparison

This table shows the differences between supported OpenAPI versions and their implementation in php-swagger-test.

## Feature Support Matrix

| Feature                        | Swagger 2.0              | OpenAPI 3.0          | OpenAPI 3.1          |
|--------------------------------|--------------------------|----------------------|----------------------|
| **Basic Features**             |
| Paths and Operations           | ✅                        | ✅                    | ✅                    |
| Request/Response Validation    | ✅                        | ✅                    | ✅                    |
| Schema Definitions             | ✅ (definitions)          | ✅ (components)       | ✅ (components)       |
| Parameters                     | ✅                        | ✅                    | ✅                    |
| **Schema Features**            |
| Basic Types                    | ✅                        | ✅                    | ✅                    |
| Objects and Arrays             | ✅                        | ✅                    | ✅                    |
| Nullable (nullable keyword)    | ⚠️ (use allowNullValues) | ✅                    | ⚠️ (deprecated)      |
| Nullable (type array)          | ❌                        | ❌                    | ✅                    |
| allOf                          | ✅                        | ✅                    | ✅                    |
| oneOf                          | ✅                        | ✅                    | ✅                    |
| anyOf                          | ❌                        | ✅                    | ✅                    |
| const                          | ❌                        | ❌                    | ✅                    |
| if/then/else                   | ❌                        | ❌                    | ✅                    |
| prefixItems (tuple validation) | ❌                        | ❌                    | ✅                    |
| **References**                 |
| $ref                           | ✅                        | ✅                    | ✅                    |
| $ref with sibling keywords     | ❌                        | ❌ (needs allOf)      | ✅                    |
| **Content Types**              |
| application/json               | ✅                        | ✅                    | ✅                    |
| application/xml                | ✅                        | ✅                    | ✅                    |
| text/html                      | ❌                        | ✅                    | ✅                    |
| Multiple content types         | ❌                        | ✅                    | ✅                    |
| **Server Configuration**       |
| Server URLs                    | host + basePath          | servers array        | servers array        |
| Server Variables               | ❌                        | ✅ (default required) | ✅ (default optional) |
| **Advanced Features**          |
| Webhooks                       | ❌                        | ❌                    | ✅                    |
| Callbacks                      | ❌                        | ⚠️ (partial)         | ⚠️ (partial)         |
| Links                          | ❌                        | ⚠️ (not implemented) | ⚠️ (not implemented) |
| **JSON Schema Compatibility**  |
| JSON Schema Version            | Custom                   | Modified subset      | Full 2020-12         |
| $schema declaration            | ❌                        | ❌                    | ✅                    |

## Legend

- ✅ Fully supported
- ⚠️ Partially supported or deprecated
- ❌ Not available

## Choosing a Version

### Use Swagger 2.0 if:

- You're maintaining legacy APIs
- You need maximum tool compatibility
- Your schemas are simple
- You're working with older systems

### Use OpenAPI 3.0 if:

- You want modern features without the latest complexity
- You need multiple content types
- You want better server configuration
- Most of your tooling supports it well
- You don't need advanced JSON Schema features

### Use OpenAPI 3.1 if:

- You need webhooks to describe incoming requests
- You want full JSON Schema 2020-12 compatibility
- You need advanced validation (if/then/else, const, tuples)
- You're starting a new project
- Your tools and infrastructure support 3.1
- You want to future-proof your API specifications

## Migration Paths

```
Swagger 2.0 ──────────────────────┐
                                   ├──> OpenAPI 3.0 ──> OpenAPI 3.1
                                   │
                                   └──> OpenAPI 3.1 (direct jump possible)
```

### Recommended Migration Strategy

1. **Swagger 2.0 → OpenAPI 3.0**: Good first step if you have complex tooling dependencies
    - Easier migration path
    - Better tool support historically
    - Can then migrate to 3.1 when ready

2. **Swagger 2.0 → OpenAPI 3.1**: Direct migration for new projects
    - Skip the intermediate step
    - Get all the latest features immediately
    - Requires more comprehensive schema updates

3. **OpenAPI 3.0 → OpenAPI 3.1**: Simple upgrade
    - Mostly compatible
    - Main change: nullable keyword → type arrays
    - Can take advantage of new features gradually

## Class Names in php-swagger-test

| Version       | Class Name                                |
|---------------|-------------------------------------------|
| Swagger 2.0   | `ByJG\ApiTools\Swagger\SwaggerSchema`     |
| OpenAPI 3.0.x | `ByJG\ApiTools\OpenApi\OpenApiSchema`     |
| OpenAPI 3.1.x | `ByJG\ApiTools\OpenApi31\OpenApi31Schema` |

All classes extend `ByJG\ApiTools\Base\Schema` and are automatically instantiated based on the specification version
when using factory methods:

```php
// Automatic version detection
$schema = \ByJG\ApiTools\Base\Schema::fromFile('/path/to/spec.json');

// Returns:
// - SwaggerSchema for "swagger": "2.0"
// - OpenApiSchema for "openapi": "3.0.x"
// - OpenApi31Schema for "openapi": "3.1.x"
```

## Version-Specific Code Examples

### Nullable Fields

```php
// Swagger 2.0
$schema = Schema::fromFile('swagger.json', allowNullValues: true);

// OpenAPI 3.0
{
  "type": "string",
  "nullable": true
}

// OpenAPI 3.1
{
  "type": ["string", "null"]
}
```

### Server URLs

```php
// Swagger 2.0
{
  "host": "api.example.com",
  "basePath": "/v1"
}
// URL: https://api.example.com/v1

// OpenAPI 3.0 & 3.1
{
  "servers": [
    {
      "url": "https://{environment}.example.com/v1",
      "variables": {
        "environment": {
          "default": "api",  // Required in 3.0, optional in 3.1
          "enum": ["api", "dev", "staging"]
        }
      }
    }
  ]
}
```

### Webhooks (OpenAPI 3.1 only)

```php
// OpenAPI 3.1 - testing incoming webhook requests
$schema = Schema::fromFile('openapi31.json');

if ($schema->hasWebhooks()) {
    $requestBody = $schema->getWebhookRequestParameters('newUser', 'post');
    $requestBody->match($incomingWebhookData);
}
```

## Further Reading

- [Migration Guide](migration-guide.md) - Detailed migration instructions
- [OpenAPI 3.1 Features](openapi-3.1-features.md) - Complete guide to 3.1 features
- [Schema Classes](schema-classes.md) - API reference for schema classes
- [OpenAPI Specification](https://spec.openapis.org/) - Official specifications