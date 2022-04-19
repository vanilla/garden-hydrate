<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the `DataHydrator` class.
 */
class DataHydratorNodeCacheTest extends TestCase {
    private $hydrator;

    /**
     * Set up a hydrator fixture for each test.
     */
    public function setUp(): void {
        parent::setUp();

        $this->hydrator = new DataHydrator();

    }

    /**
     * Test that node cache is working and counter is valid.
     */
    public function testRootParam(): void {
        $spec = ['$hydrate' => 'param', 'ref' => 'foo'];

        $this->hydrator->resolve($spec, ['foo' => 'bar']); //resolved once on a single request
        $midCount = $this->hydrator->getResolveCount();
        $this->assertSame(1, $midCount, 'missed count first');

        $this->hydrator->resolve($spec, ['foo' => 'bar']); //resolving same again now found in cache
        $dupCount = $this->hydrator->getResolveCount();
        $this->assertSame(1, $dupCount, 'duplicate processed; not pulled from cache');

        $spec = ['$hydrate' => 'param', 'ref' => 'baz'];
        $this->hydrator->resolve($spec, ['baz' => 'bar']); //resolved new on a separate request not in cache
        $nondupCount = $this->hydrator->getResolveCount();
        $this->assertSame(2, $nondupCount, 'not fresh resolve');

        $this->hydrator->clearResolverCache(); // clear resolver cache and count
        $finalCount = $this->hydrator->getResolveCount();
        $this->assertSame(0, $finalCount, 'cache not cleared');
    }

    /**
     * Test that node cache is working and counter is valid on nested elements.
     */
    public function testNestedResolvers(): void {
        $spec = ['$hydrate' => 'param', 'ref' => ['$hydrate' => 'param', 'ref' => 'foo']];
        $this->hydrator->resolve($spec, ['foo' => 'bar', 'bar' => 'baz']);
        $nestedCount = $this->hydrator->getResolveCount();
        $this->assertSame(2, $nestedCount, 'nested not counted'); // count resolve nested
        $this->hydrator->clearResolverCache(); // clear resolver count
        $finalCount = $this->hydrator->getResolveCount();
        $this->assertSame(0, $finalCount, 'cache not cleared'); // count cache cleared
    }

    /**
     * Test that node cache is working and valid on nested elements.
     */
    public function testNestedResolversDuplicates(): void {
        $spec = ['$hydrate' => 'param', 'ref' => ['$hydrate' => 'param', 'ref' => 'foo']];
        $this->hydrator->resolve($spec, ['foo' => 'bar', 'bar' => 'baz', 'baz' => 'bip']);
        $nestedCount = $this->hydrator->getResolveCount();
        $this->assertSame(2, $nestedCount, 'nested not counted'); // count resolve nested
        $this->hydrator->clearResolverCache(); // clear resolver count
        $finalCount = $this->hydrator->getResolveCount();
        $this->assertSame(0, $finalCount, 'cache not cleared'); // count cache cleared
    }
}
