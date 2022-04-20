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
        $cache = $this->hydrator->getCache($layoutCacheNodeKey);
        $cacheHit = $cache->get();
        $this->assertSame('bar', $cacheHit);

        $spec = ['$hydrate' => 'param', 'ref' => 'bar']; //second req diff from first
        $layoutCacheNodeKey2 = md5(json_encode($spec));
        $this->hydrator->resolve($spec, ['bar' => 'baz']);
        $cache2 = $this->hydrator->getCache($layoutCacheNodeKey2);
        $cacheHit = $cache2->get();
        $this->assertSame('baz', $cacheHit);
        $this->assertSame(2, $this->hydrator->nodeResolved); // node was processed - incrementing counter

        $spec = ['$hydrate' => 'param', 'ref' => 'foo']; //third req same as first
        $this->hydrator->resolve($spec, ['foo' => 'bar']);
        $this->assertSame(2, $this->hydrator->nodeResolved); // node returned from cache - not incremented

        $spec = ['$hydrate' => 'param', 'ref' => 'bif']; //fourth req new diff
        $this->hydrator->resolve($spec, ['bif' => 'bun']);
        $this->assertSame(3, $this->hydrator->nodeResolved); // node was processed - incrementing counter

    }
}
