# MethorZ OpenAPI Generator

**Automatic OpenAPI 3.0 specification generator from routes and DTOs**

[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Automatically generates OpenAPI specifications by analyzing your application's routes and Data Transfer Objects (DTOs). Perfect for Mezzio, Laminas, and any PSR-15 application.

---

## ✨ Features

- 🚀 **Automatic Generation**: Scans routes and DTOs to generate complete OpenAPI specs
- 📝 **DTO Analysis**: Extracts request/response schemas from PHP DTOs with property promotion
- ✅ **Validation Integration**: Reads Symfony Validator attributes for schema constraints
- 🎯 **Handler Detection**: Automatically finds request and response DTOs in handlers
- 📦 **Multiple Formats**: Generates both YAML and JSON outputs
- 🔧 **Zero Configuration**: Works out-of-the-box with sensible defaults
- 🎨 **Customizable**: Configure via application config
- 🔗 **Nested DTOs**: Automatically generates schemas for nested DTO references
- 📚 **Collections**: Supports typed arrays with `@param array<Type>` PHPDoc
- 🎲 **Enums**: Full support for backed and unit enums (PHP 8.1+)
- 🔀 **Union Types**: Generates `oneOf` schemas for union types (PHP 8.0+)
- ⚡ **Performance**: Schema caching for efficient generation

---

## 📋 Requirements

This package requires PHP 8.2+ and uses the following runtime dependencies:

| Package | Purpose | Framework Required? |
|---------|---------|---------------------|
| `psr/container` | PSR-11 Container Interface | ❌ No |
| `symfony/console` | CLI command handling | ❌ No (standalone utility) |
| `symfony/yaml` | YAML file parsing/writing | ❌ No (standalone utility) |

> **Note**: The Symfony packages used are **standalone utility libraries**, not framework components. They work independently without the Symfony framework and are used by many non-Symfony projects (Composer, PHPStan, PHPUnit, etc.).

---

## 📦 Installation

```bash
composer require methorz/openapi-generator
```

---

## 🚀 Quick Start

### 1. **Register the Command**

Add to your application's command configuration:

```php
// config/autoload/dependencies.global.php
use Methorz\OpenApi\Command\GenerateOpenApiCommand;

return [
    'dependencies' => [
        'factories' => [
            GenerateOpenApiCommand::class => function ($container) {
                return new GenerateOpenApiCommand($container);
            },
        ],
    ],
];
```

### 2. **Generate Specification**

```bash
php bin/console openapi:generate
```

This will create:
- `public/openapi.yaml` - YAML format
- `public/openapi.json` - JSON format

---

## 📖 Usage

### **Basic Configuration**

```php
// config/autoload/openapi.global.php
return [
    'openapi' => [
        'title' => 'My API',
        'version' => '1.0.0',
    ],
];
```

### **Example Handler**

The generator automatically analyzes your handlers:

```php
namespace App\Handler;

use App\Request\CreateItemRequest;
use App\Response\ItemResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateItemHandler implements RequestHandlerInterface
{
    public function handle(
        ServerRequestInterface $request,
        CreateItemRequest $dto // ← Request DTO detected
    ): ItemResponse {           // ← Response DTO detected
        // Handler logic...
    }
}
```

### **Example Request DTO**

```php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateItemRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 10, max: 500)]
        public string $description,

        #[Assert\Email]
        public string $email,
    ) {}
}
```

**Generated Schema**:
```yaml
components:
  schemas:
    CreateItemRequest:
      type: object
      required:
        - name
        - description
        - email
      properties:
        name:
          type: string
          minLength: 3
          maxLength: 100
        description:
          type: string
          minLength: 10
          maxLength: 500
        email:
          type: string
          format: email
```

---

## 📋 Supported Validation Attributes

The generator extracts constraints from Symfony Validator attributes:

| Attribute | OpenAPI Property |
|-----------|------------------|
| `@Assert\NotBlank` | `required: true` |
| `@Assert\Length(min, max)` | `minLength`, `maxLength` |
| `@Assert\Range(min, max)` | `minimum`, `maximum` |
| `@Assert\Email` | `format: email` |
| `@Assert\Url` | `format: uri` |
| `@Assert\Uuid` | `format: uuid` |

---

## 🚀 Advanced Features

### **Enum Support**

Generates enum schemas from PHP 8.1+ backed enums:

```php
enum StatusEnum: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}

final readonly class CreateItemRequest
{
    public function __construct(
        public StatusEnum $status,
    ) {}
}
```

**Generated Schema**:
```yaml
CreateItemRequest:
  type: object
  properties:
    status:
      type: string
      enum: ['draft', 'active', 'archived']
```

### **Nested DTOs**

Automatically generates schemas for nested DTO objects:

```php
final readonly class AddressDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $street,

        #[Assert\NotBlank]
        public string $city,

        public ?string $country = null,
    ) {}
}

final readonly class CreateUserRequest
{
    public function __construct(
        public string $name,
        public AddressDto $address,              // ← Nested DTO
        public ?AddressDto $billingAddress = null, // ← Nullable nested DTO
    ) {}
}
```

**Generated Schema**:
```yaml
CreateUserRequest:
  type: object
  required: ['name', 'address']
  properties:
    name:
      type: string
    address:
      $ref: '#/components/schemas/AddressDto'
    billingAddress:
      $ref: '#/components/schemas/AddressDto'
      nullable: true

AddressDto:
  type: object
  required: ['street', 'city']
  properties:
    street:
      type: string
    city:
      type: string
    country:
      type: string
      nullable: true
```

### **Typed Collections**

Supports typed arrays using PHPDoc annotations:

```php
/**
 * @param array<int, AddressDto> $addresses
 * @param array<string> $tags
 */
final readonly class CreateOrderRequest
{
    public function __construct(
        public string $orderId,
        public array $addresses,
        public array $tags,
    ) {}
}
```

**Generated Schema**:
```yaml
CreateOrderRequest:
  type: object
  properties:
    orderId:
      type: string
    addresses:
      type: array
      items:
        $ref: '#/components/schemas/AddressDto'
    tags:
      type: array
      items:
        type: string
```

### **Union Types**

Generates `oneOf` schemas for union types:

```php
final readonly class FlexibleRequest
{
    public function __construct(
        public string|int $identifier,  // ← Union type
    ) {}
}
```

**Generated Schema**:
```yaml
FlexibleRequest:
  type: object
  properties:
    identifier:
      oneOf:
        - type: string
        - type: integer
```

---

## 🎯 Features

### **Route Detection**

Scans your application's route configuration:

```php
// config/autoload/routes.global.php
return [
    'routes' => [
        [
            'path' => '/api/v1/items',
            'middleware' => [CreateItemHandler::class],
            'allowed_methods' => ['POST'],
        ],
    ],
];
```

### **Automatic Operation Generation**

Creates OpenAPI operations with:
- HTTP method (GET, POST, PUT, DELETE, etc.)
- Path parameters (extracted from `{id}` patterns)
- Request body (for POST/PUT/PATCH)
- Response schemas
- Summary and operationId
- Tags (from module namespace)

### **Path Parameters**

Automatically detects and types path parameters:

```php
'/api/v1/items/{id}' → parameter: id (format: uuid)
'/api/v1/users/{userId}' → parameter: userId (type: integer)
```

---

## 📂 Generated Output

### **OpenAPI Structure**

```yaml
openapi: 3.0.0
info:
  title: My API
  version: 1.0.0
  description: Automatically generated from routes and DTOs
servers:
  - url: http://localhost:8080
    description: Local development
paths:
  /api/v1/items:
    post:
      operationId: createItem
      summary: create item
      tags:
        - Items
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateItemRequest'
      responses:
        201:
          description: Success
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ItemResponse'
        400:
          description: Bad Request
        404:
          description: Not Found
components:
  schemas:
    CreateItemRequest:
      # ... schema definition
    ItemResponse:
      # ... schema definition
```

---

## 🔧 Configuration

### **Full Configuration Example**

```php
// config/autoload/openapi.global.php
return [
    'openapi' => [
        'title' => 'My API',
        'version' => '1.0.0',
        'description' => 'API for managing items',
        'servers' => [
            [
                'url' => 'https://api.example.com',
                'description' => 'Production',
            ],
            [
                'url' => 'http://localhost:8080',
                'description' => 'Development',
            ],
        ],
    ],
];
```

---

## 📊 Integration with Swagger UI

View your generated OpenAPI specification:

```bash
# Install Swagger UI
composer require swagger-api/swagger-ui

# Access at:
http://localhost:8080/swagger-ui
```

Or use online tools:
- [Swagger Editor](https://editor.swagger.io)
- [ReDoc](https://redocly.github.io/redoc/)

---

## 🧪 Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Code style check
composer cs-check

# Fix code style
composer cs-fix

# Static analysis
composer analyze

# All quality checks
composer quality
```

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Write tests for new features
4. Ensure all quality checks pass (`composer quality`)
5. Submit a pull request

---

## 📄 License

MIT License. See [LICENSE](LICENSE) for details.

---

## 🔗 Related Packages

This package is part of the MethorZ HTTP middleware ecosystem:

| Package | Description |
|---------|-------------|
| **[methorz/http-dto](https://github.com/methorz/http-dto)** | Automatic HTTP ↔ DTO conversion with validation |
| **[methorz/http-problem-details](https://github.com/methorz/http-problem-details)** | RFC 7807 error handling middleware |
| **[methorz/http-cache-middleware](https://github.com/methorz/http-cache-middleware)** | HTTP caching with ETag support |
| **[methorz/http-request-logger](https://github.com/methorz/http-request-logger)** | Structured logging with request tracking |
| **[methorz/openapi-generator](https://github.com/methorz/openapi-generator)** | Automatic OpenAPI spec generation (this package) |

These packages work together seamlessly in PSR-15 applications.

---

## 🙏 Acknowledgments

Built with:
- [Symfony Console](https://symfony.com/doc/current/components/console.html)
- [Symfony YAML](https://symfony.com/doc/current/components/yaml.html)
- [Symfony Validator](https://symfony.com/doc/current/validation.html)

---

## 📞 Support

- **Issues**: [GitHub Issues](https://github.com/methorz/openapi-generator/issues)
- **Discussions**: [GitHub Discussions](https://github.com/methorz/openapi-generator/discussions)

---

## 🔗 Links

- [Changelog](CHANGELOG.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

---

Made with ❤️ by [Thorsten Merz](https://github.com/methorz)

