<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Documentation;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Modules\Shared\Infrastructure\Laravel\Middleware\ApiLocaleMiddleware;

final readonly class OpenApiDocumentationDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        return $this->enhanceOpenApiDocumentation($openApi);
    }

    private function enhanceOpenApiDocumentation(OpenApi $openApi): OpenApi
    {
        // Update API info with internationalization details
        $info = $openApi->getInfo();
        $enhancedInfo = $info->withDescription(
            $info->getDescription() . "\n\n" .
            "## Internationalization\n\n" .
            "This API supports multiple languages through the `Accept-Language` header or `locale` query parameter.\n\n" .
            "**Supported Languages:**\n" .
            "- `en` - English (default)\n" .
            "- `nl` - Dutch\n" .
            "- `fr` - French\n\n" .
            "**Usage Examples:**\n" .
            "- Header: `Accept-Language: nl,fr;q=0.9,en;q=0.8`\n" .
            "- Query: `?locale=fr`\n\n" .
            'All error messages and API responses will be localized based on the detected language preference.',
        );

        // Add additional servers for different environments
        $servers = $openApi->getServers();
        $servers[] = new Server('https://api.acme-corp.com/v1', 'Production');
        $servers[] = new Server('https://staging-api.acme-corp.com/v1', 'Staging');
        $servers[] = new Server('http://localhost:8000/api/v1', 'Development');

        // Enhance paths with locale parameters and examples
        $paths = $openApi->getPaths();
        $enhancedPaths = new Paths;

        foreach ($paths->getPaths() as $path => $pathItem) {
            $enhancedPaths->addPath($path, $this->enhancePathItem($pathItem));
        }

        return $openApi
            ->withInfo($enhancedInfo)
            ->withServers($servers)
            ->withPaths($enhancedPaths);
    }

    private function enhancePathItem(PathItem $pathItem): PathItem
    {
        $operations = [];

        // Enhance each operation (GET, POST, PUT, DELETE, etc.)
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            $operation = $pathItem->{'get' . ucfirst($method)}();

            if ($operation) {
                $operations[$method] = $this->enhanceOperation($operation);
            }
        }

        return $pathItem
            ->withGet($operations['get'] ?? null)
            ->withPost($operations['post'] ?? null)
            ->withPut($operations['put'] ?? null)
            ->withPatch($operations['patch'] ?? null)
            ->withDelete($operations['delete'] ?? null);
    }

    private function enhanceOperation(Operation $operation): Operation
    {
        // Add locale parameter to all operations
        $parameters = $operation->getParameters() ?? [];

        // Add Accept-Language header parameter
        $parameters[] = new Parameter(
            name: 'Accept-Language',
            in: 'header',
            description: 'Preferred language for API responses. Supports: ' . implode(', ', ApiLocaleMiddleware::getSupportedLocales()),
            required: false,
            schema: [
                'type' => 'string',
                'example' => 'en,nl;q=0.9,fr;q=0.8',
                'default' => ApiLocaleMiddleware::getDefaultLocale(),
            ],
        );

        // Add locale query parameter
        $parameters[] = new Parameter(
            name: 'locale',
            in: 'query',
            description: 'Override locale for this request',
            required: false,
            schema: [
                'type' => 'string',
                'enum' => ApiLocaleMiddleware::getSupportedLocales(),
                'example' => 'en',
            ],
        );

        // Enhance responses with localized examples
        $responses = $operation->getResponses() ?? [];
        $enhancedResponses = [];

        foreach ($responses as $statusCode => $response) {
            $enhancedResponses[$statusCode] = $this->enhanceResponse($response, (string) $statusCode);
        }

        return $operation
            ->withParameters($parameters)
            ->withResponses($enhancedResponses);
    }

    private function enhanceResponse(Response $response, string $statusCode): Response
    {
        $content = $response->getContent();

        // Convert content to array if it's an ArrayObject or null
        if ($content instanceof ArrayObject) {
            $content = $content->getArrayCopy();
        } elseif ($content === null) {
            $content = [];
        }

        if ($statusCode === '200' || $statusCode === '201') {
            // Add success response examples in different languages
            $content['application/json']['examples'] = [
                'english' => [
                    'summary' => 'Success response in English',
                    'value' => [
                        'success' => true,
                        'data' => ['id' => 1, 'title' => 'Campaign Title'],
                        'message' => 'Request completed successfully',
                        'meta' => [
                            'locale' => 'en',
                            'timestamp' => '2024-01-01T12:00:00Z',
                        ],
                    ],
                ],
                'dutch' => [
                    'summary' => 'Success response in Dutch',
                    'value' => [
                        'success' => true,
                        'data' => ['id' => 1, 'title' => 'Campagne Titel'],
                        'message' => 'Verzoek succesvol voltooid',
                        'meta' => [
                            'locale' => 'nl',
                            'timestamp' => '2024-01-01T12:00:00Z',
                        ],
                    ],
                ],
                'french' => [
                    'summary' => 'Success response in French',
                    'value' => [
                        'success' => true,
                        'data' => ['id' => 1, 'title' => 'Titre de Campagne'],
                        'message' => 'Demande traitée avec succès',
                        'meta' => [
                            'locale' => 'fr',
                            'timestamp' => '2024-01-01T12:00:00Z',
                        ],
                    ],
                ],
            ];
        } elseif ($statusCode === '400') {
            // Add error response examples
            $content['application/json']['examples'] = [
                'validation_error_english' => [
                    'summary' => 'Validation error in English',
                    'value' => [
                        'success' => false,
                        'error' => [
                            'type' => 'validation_error',
                            'message' => 'The given data was invalid',
                            'code' => 'VALIDATION_FAILED',
                            'details' => [
                                'title' => ['The title field is required'],
                                'amount' => ['The amount must be greater than 0'],
                            ],
                        ],
                        'meta' => [
                            'locale' => 'en',
                            'timestamp' => '2024-01-01T12:00:00Z',
                            'request_id' => 'req_123456789',
                        ],
                    ],
                ],
                'validation_error_dutch' => [
                    'summary' => 'Validation error in Dutch',
                    'value' => [
                        'success' => false,
                        'error' => [
                            'type' => 'validation_error',
                            'message' => 'De opgegeven gegevens zijn ongeldig',
                            'code' => 'VALIDATION_FAILED',
                            'details' => [
                                'title' => ['Het titel veld is verplicht'],
                                'amount' => ['Het bedrag moet groter zijn dan 0'],
                            ],
                        ],
                        'meta' => [
                            'locale' => 'nl',
                            'timestamp' => '2024-01-01T12:00:00Z',
                            'request_id' => 'req_123456789',
                        ],
                    ],
                ],
            ];
        }

        return $response->withContent(new ArrayObject($content));
    }
}
