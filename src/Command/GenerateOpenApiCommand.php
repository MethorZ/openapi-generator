<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Command;

use MethorZ\OpenApi\Analyzer\HandlerAnalyzer;
use MethorZ\OpenApi\Config\OpenApiConfig;
use MethorZ\OpenApi\Generator\DtoSchemaGenerator;
use MethorZ\OpenApi\Generator\RouteScanner;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Throwable;

use function file_get_contents;
use function file_put_contents;
use function json_encode;
use function preg_match;
use function sprintf;

/**
 * Generates OpenAPI specification from routes and DTOs
 */
final class GenerateOpenApiCommand extends Command
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('openapi:generate')
            ->setDescription('Generates OpenAPI specification automatically from routes and DTOs')
            ->addArgument(
                'config',
                InputArgument::OPTIONAL,
                'Path to OpenAPI configuration file (YAML)',
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output path for OpenAPI specification',
                'docs/openapi.yaml',
            )
            ->addOption(
                'json',
                'j',
                InputOption::VALUE_NONE,
                'Also generate JSON output',
            )
            ->addOption(
                'title',
                't',
                InputOption::VALUE_REQUIRED,
                'API title',
            )
            ->addOption(
                'version',
                'v',
                InputOption::VALUE_REQUIRED,
                'API version',
            );
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded -- Command naturally has many branches
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>🚀 Starting OpenAPI generation...</info>');
        $output->writeln('');

        // Load OpenAPI configuration
        $configFile = $input->getArgument('config');
        $openapiConfig = $configFile !== null && is_string($configFile)
            ? OpenApiConfig::fromYamlFile($configFile)
            : OpenApiConfig::default();

        // Override with CLI options
        $info = $openapiConfig->info;
        $titleOption = $input->getOption('title');

        if ($titleOption !== null && is_string($titleOption)) {
            $info['title'] = $titleOption;
        }

        $versionOption = $input->getOption('version');

        if ($versionOption !== null && is_string($versionOption)) {
            $info['version'] = $versionOption;
        }

        $outputOption = $input->getOption('output');
        /** @var string $outputPath */
        $outputPath = is_string($outputOption)
            ? $outputOption
            : $openapiConfig->outputPath;

        /** @var bool $generateJson */
        $generateJson = (bool) $input->getOption('json') || $openapiConfig->generateJson;

        // Reconstruct config with overrides
        $infoChanged = $info !== $openapiConfig->info;
        $pathChanged = $outputPath !== $openapiConfig->outputPath;
        $jsonChanged = $generateJson !== $openapiConfig->generateJson;

        if ($infoChanged || $pathChanged || $jsonChanged) {
            $openapiConfig = new OpenApiConfig(
                info: $info,
                servers: $openapiConfig->servers,
                securitySchemes: $openapiConfig->securitySchemes,
                tags: $openapiConfig->tags,
                security: $openapiConfig->security,
                outputPath: $outputPath,
                generateJson: $generateJson,
            );
        }

        // Get application config from container
        $config = $this->container->get('config');

        if (!is_array($config)) {
            $output->writeln('<error>❌ Could not load application config</error>');

            return Command::FAILURE;
        }

        // Initialize generators
        $handlerAnalyzer = new HandlerAnalyzer();
        $schemaGenerator = new DtoSchemaGenerator();
        $routeScanner = new RouteScanner($config, $handlerAnalyzer, $schemaGenerator);

        // Step 1: Scan routes
        $output->writeln('<info>📍 Scanning routes from configuration...</info>');
        $paths = $routeScanner->scanRoutes();
        $output->writeln(sprintf('<comment>   Found %d unique paths</comment>', count($paths)));

        // Step 2: Find and generate DTO schemas
        $output->writeln('<info>📦 Scanning DTOs...</info>');
        $dtoClasses = $this->findAllDtos('src');

        $schemas = [];

        foreach ($dtoClasses as $dtoClass) {
            try {
                $schema = $schemaGenerator->generate($dtoClass);

                if (!empty($schema)) {
                    $schemaName = $schemaGenerator->getSchemaName($dtoClass);
                    $schemas[$schemaName] = $schema;
                }
            } catch (Throwable $e) {
                $output->writeln(sprintf(
                    '<error>   Failed to generate schema for %s: %s</error>',
                    $this->getShortClassName($dtoClass),
                    $e->getMessage(),
                ));
            }
        }

        $output->writeln(sprintf('<comment>   Generated %d schemas</comment>', count($schemas)));

        // Step 3: Build OpenAPI spec
        $output->writeln('<info>📝 Building OpenAPI specification...</info>');

        $openApiSpec = [
            'openapi' => '3.0.0',
            'info' => $openapiConfig->info,
            'servers' => $openapiConfig->servers,
            'paths' => $paths,
            'components' => [
                'schemas' => $schemas,
            ],
        ];

        // Add security schemes if configured
        if (! empty($openapiConfig->securitySchemes)) {
            $openApiSpec['components']['securitySchemes'] = $openapiConfig->securitySchemes;
        }

        // Add global security if configured
        if (! empty($openapiConfig->security)) {
            $openApiSpec['security'] = $openapiConfig->security;
        }

        // Add tags if configured
        if (! empty($openapiConfig->tags)) {
            $openApiSpec['tags'] = $openapiConfig->tags;
        }

        // Step 4: Write output files
        $output->writeln('<info>💾 Writing output files...</info>');

        try {
            $yaml = Yaml::dump($openApiSpec, 20, 2);

            // Create output directory if it doesn't exist
            $outputDir = dirname($openapiConfig->outputPath);

            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            file_put_contents($openapiConfig->outputPath, $yaml);
            $output->writeln(sprintf('<comment>   ✓ %s</comment>', $openapiConfig->outputPath));

            // Generate JSON if configured
            if ($openapiConfig->generateJson) {
                $json = json_encode($openApiSpec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $jsonPath = str_replace('.yaml', '.json', $openapiConfig->outputPath);
                file_put_contents($jsonPath, $json);
                $output->writeln(sprintf('<comment>   ✓ %s</comment>', $jsonPath));
            }
        } catch (Throwable $e) {
            $output->writeln(sprintf('<error>   Failed to write files: %s</error>', $e->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln('');
        $output->writeln('<info>✅ OpenAPI specification generated successfully!</info>');

        return Command::SUCCESS;
    }

    /**
     * Find all DTO classes in the source directory
     *
     * @return array<int, string>
     */
    private function findAllDtos(string $directory): array
    {
        $dtoClasses = [];

        if (!is_dir($directory)) {
            return $dtoClasses;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $filename = $file->getFilename();

            // Look for Request and Response files
            if (!str_ends_with($filename, 'Request.php') && !str_ends_with($filename, 'Response.php')) {
                continue;
            }

            $className = $this->extractClassNameFromFile($file->getPathname());

            if ($className && class_exists($className)) {
                $dtoClasses[] = $className;
            }
        }

        return $dtoClasses;
    }

    /**
     * Extract fully qualified class name from PHP file
     */
    private function extractClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        // Extract namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return null;
        }

        $namespace = $namespaceMatch[1];

        // Extract class name
        if (!preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return null;
        }

        return $namespace . '\\' . $classMatch[1];
    }

    private function getShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);

        return end($parts);
    }
}
