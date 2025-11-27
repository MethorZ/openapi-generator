<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Analyzer;

use MethorZ\OpenApi\Analyzer\HandlerAnalyzer;
use MethorZ\OpenApi\Tests\Fixtures\ExampleDto;
use MethorZ\OpenApi\Tests\Fixtures\TestHandler;
use PHPUnit\Framework\TestCase;
use stdClass;

final class HandlerAnalyzerTest extends TestCase
{
    private HandlerAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new HandlerAnalyzer();
    }

    public function testAnalyzesHandlerWithDtos(): void
    {
        $result = $this->analyzer->analyze(TestHandler::class);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('requestDto', $result);
        $this->assertArrayHasKey('responseDto', $result);
    }

    public function testExtractsRequestDto(): void
    {
        $result = $this->analyzer->analyze(TestHandler::class);

        $this->assertEquals(ExampleDto::class, $result['requestDto']);
    }

    public function testExtractsResponseDto(): void
    {
        $result = $this->analyzer->analyze(TestHandler::class);

        $this->assertEquals(ExampleDto::class, $result['responseDto']);
    }

    public function testReturnsNullForNonExistentClass(): void
    {
        $result = $this->analyzer->analyze('NonExistentClass');

        $this->assertNull($result['requestDto']);
        $this->assertNull($result['responseDto']);
    }

    public function testReturnsNullForClassWithoutInvokeMethod(): void
    {
        $result = $this->analyzer->analyze(stdClass::class);

        $this->assertNull($result['requestDto']);
        $this->assertNull($result['responseDto']);
    }
}
