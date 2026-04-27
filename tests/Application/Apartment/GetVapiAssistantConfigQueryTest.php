<?php

namespace App\Tests\Application\Apartment;

use App\Application\Apartment\Query\GetVapiAssistantConfigQuery;
use App\Domain\Apartment\VapiAssistantConfig;
use App\Domain\Apartment\VapiAssistantConfigRepositoryInterface;
use PHPUnit\Framework\TestCase;

class GetVapiAssistantConfigQueryTest extends TestCase
{
    public function testExecuteReturnsConfig(): void
    {
        $repositoryMock = $this->createMock(VapiAssistantConfigRepositoryInterface::class);
        $config = new VapiAssistantConfig('Prompt', 'First message', 30);

        $repositoryMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $query = new GetVapiAssistantConfigQuery($repositoryMock);
        $result = $query->execute();

        $this->assertSame($config, $result);
    }

    public function testExecuteReturnsNullWhenNoConfig(): void
    {
        $repositoryMock = $this->createMock(VapiAssistantConfigRepositoryInterface::class);

        $repositoryMock->expects($this->once())
            ->method('getConfig')
            ->willReturn(null);

        $query = new GetVapiAssistantConfigQuery($repositoryMock);
        $result = $query->execute();

        $this->assertNull($result);
    }
}
