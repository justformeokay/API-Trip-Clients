<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\SearchService;
use App\Domain\Contracts\TripRepositoryInterface;
use App\Exceptions\ValidationException;

class SearchServiceTest extends TestCase
{
    private SearchService $service;
    /** @var TripRepositoryInterface&MockObject */
    private TripRepositoryInterface $tripRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tripRepo = $this->createMock(TripRepositoryInterface::class);
        $this->service  = new SearchService($this->tripRepo);
    }

    public function testSearchWithoutGeoCallsStandardSearch(): void
    {
        $this->tripRepo
            ->expects($this->once())
            ->method('search')
            ->willReturn(['data' => [], 'total' => 0]);

        $this->tripRepo->expects($this->never())->method('findByRadius');

        $result = $this->service->search(['keyword' => 'Rinjani'], 1, 15);

        $this->assertArrayHasKey('data', $result);
    }

    public function testSearchWithGeoCallsRadius(): void
    {
        $this->tripRepo
            ->expects($this->once())
            ->method('findByRadius')
            ->willReturn(['data' => [], 'total' => 0]);

        $this->tripRepo->expects($this->never())->method('search');

        $result = $this->service->search(
            ['latitude' => -8.4111, 'longitude' => 116.4671, 'radius_km' => 50],
            1,
            15
        );

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('search_center', $result);
    }

    public function testSearchThrowsOnInvalidLatitude(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->search(
            ['latitude' => 999, 'longitude' => 116.4671],
            1,
            15
        );
    }

    public function testSearchThrowsOnInvalidLongitude(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->search(
            ['latitude' => -8.4111, 'longitude' => 999],
            1,
            15
        );
    }

    public function testSearchThrowsWhenRadiusExceedsMax(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->search(
            ['latitude' => -8.4111, 'longitude' => 116.4671, 'radius_km' => 501],
            1,
            15
        );
    }
}
