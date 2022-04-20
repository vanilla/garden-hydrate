<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the `DataHydrator` class.
 */
class DataHydratorNodeCacheTest extends TestCase {

    /** @var DataHydrator  */
    private $hydrator;

    /**
     * Set up a hydrator fixture for each test.
     */
    public function setUp(): void {
        parent::setUp();
        $this->hydrator = new DataHydrator();
    }

    /**
     * Test that node cache contains results after resolved
     */
    public function testRootParam(): void {
        $spec = ['$hydrate' => 'param', 'ref' => 'foo']; //first req
        $layoutCacheNodeKey = md5(json_encode($spec));
        $this->hydrator->resolve($spec, ['foo' => 'bar']);
        $cache = $this->hydrator->getSimpleCache();
        $cacheData = $cache->get($layoutCacheNodeKey);
        $this->assertSame($cacheData, 'bar');
        $this->assertTrue($cache->has($layoutCacheNodeKey));

        $spec = ['$hydrate' => 'param', 'ref' => 'bar']; //second req
        $layoutCacheNodeKey2 = md5(json_encode($spec));
        $this->hydrator->resolve($spec, ['bar' => 'baz']);
        $cache = $this->hydrator->getSimpleCache();
        $cacheData = $cache->get($layoutCacheNodeKey2);
        $this->assertSame($cacheData, 'baz');
        $this->assertTrue($cache->has($layoutCacheNodeKey2));


        $spec = ['$hydrate' => 'param', 'ref' => 'foo']; //same as first now from cache
        $layoutCacheNodeKey = md5(json_encode($spec));
        $this->hydrator->resolve($spec, ['foo' => 'bar']);
        $cacheData = $cache->get($layoutCacheNodeKey);
        $this->assertSame($cacheData, 'bar');
    }
}
