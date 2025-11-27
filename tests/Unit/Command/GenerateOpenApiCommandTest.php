<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Command;

use MethorZ\OpenApi\Command\GenerateOpenApiCommand;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;

final class GenerateOpenApiCommandTest extends TestCase
{
    private function createMockContainer(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturn([]);

        return $container;
    }

    public function testCommandIsConfigured(): void
    {
        $command = new GenerateOpenApiCommand($this->createMockContainer());

        $this->assertSame('openapi:generate', $command->getName());
        $this->assertNotEmpty($command->getDescription());
    }

    public function testCommandHasConfigArgument(): void
    {
        $command = new GenerateOpenApiCommand($this->createMockContainer());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('config'));
    }

    public function testCommandHasOutputOption(): void
    {
        $command = new GenerateOpenApiCommand($this->createMockContainer());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('output'));
    }

    public function testCommandCanBeAddedToApplication(): void
    {
        $application = new Application();
        $command = new GenerateOpenApiCommand($this->createMockContainer());

        $application->add($command);

        $this->assertTrue($application->has('openapi:generate'));
    }

    public function testCommandHasName(): void
    {
        $command = new GenerateOpenApiCommand($this->createMockContainer());

        $this->assertSame('openapi:generate', $command->getName());
    }
}
