<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types=1);

namespace tests\AppBundle\Routing;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use VCR\VCR;

class SlugRouterWrapperTest extends KernelTestCase
{
    public function testCategoryRouting()
    {
        $kernel = static::bootKernel();
        $router = $kernel->getContainer()->get('router');

        $request = Request::create('/informatique');
        $result = $router->matchRequest($request);

        $this->assertEquals('view_category', $result['_route']);
        $this->assertEquals(3, $request->attributes->get('categoryId'));
    }

    public function test404SlugRouting()
    {
        $kernel = static::bootKernel();
        $router = $kernel->getContainer()->get('router');

        $request = Request::create('/404-slug');

        $this->expectException(ResourceNotFoundException::class);
        try {
            $router->matchRequest($request);
        } catch(ResourceNotFoundException $exception) {
            $this->assertTrue($request->attributes->get('resolve_slug', false));
            throw $exception;
        }
    }

    public function testNoSlugResolutionWhenPathDoesntMatch()
    {
        $kernel = static::bootKernel();
        $router = $kernel->getContainer()->get('router');

        $request = Request::create('/doesnt+match=slug*regexp');

        $this->expectException(ResourceNotFoundException::class);
        try {
            $router->matchRequest($request);
        } catch(ResourceNotFoundException $exception) {
            $this->assertFalse($request->attributes->has('resolve_slug'));
            throw $exception;
        }
    }

    public function testRouteWithoutSlugsAreNotOverrided()
    {
        $kernel = static::bootKernel();
        $router = $kernel->getContainer()->get('router');

        $request = Request::create('/profile');

        $result = $router->matchRequest($request);

        $this->assertEquals('profile', $result['_route']);
        $this->assertFalse($request->attributes->has('resolve_slug'));
    }

    public function testProductRouting()
    {
        $kernel = static::bootKernel();
        $router = $kernel->getContainer()->get('router');

        $request = Request::create('/informatique/ecrans/voluptas-nostrum-ea-consequatur');
        $result = $router->matchRequest($request);

        $this->assertEquals('product', $result['_route']);
        $this->assertEquals(1, $request->attributes->get('productId'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        VCR::turnOn();
        VCR::insertCassette((new \ReflectionClass($this))->getShortName().DIRECTORY_SEPARATOR.$this->getName().'.yml');
    }

    protected function tearDown(): void
    {
        VCR::turnOff();

        parent::tearDown();
    }
}
