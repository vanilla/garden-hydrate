<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Tests\Fixtures\ExceptionThrowerResolver;
use Garden\Hydrate\Tests\Fixtures\TestExceptionHandler;
use Garden\Hydrate\Tests\Fixtures\TestStringResolver;
use PHPUnit\Framework\TestCase;

/**
 * Tests for cases that were caught with mutation testing.
 */
class MutationsTest extends TestCase {
    private $hydrator;

    /**
     * Set up a hydrator fixture for each test.
     */
    public function setUp(): void {
        parent::setUp();

        $this->hydrator = new DataHydrator();
        $this->hydrator
            ->setExceptionHandler(new TestExceptionHandler())
            ->registerResolver('exception', new ExceptionThrowerResolver())
            ->registerResolver('str', new TestStringResolver('a'));
    }

    /**
     * The `'$type'` field shouldn't expand.
     */
    public function testStaticTypeField() {
        $spec = [DataHydrator::KEY_TYPE => [DataHydrator::KEY_TYPE => 'param', 'ref' => 'foo']];
        $this->expectException(\Throwable::class);
        $actual = $this->hydrator->hydrate($spec, ['foo' => 'literal']);
    }

    /**
     * The `'$middleware'` field shouldn't expand.
     */
    public function testStaticMiddlewareField() {
        $spec = [DataHydrator::KEY_MIDDLEWARE => [DataHydrator::KEY_TYPE => 'param', 'ref' => 'foo']];
        $this->expectException(\Throwable::class);
        $this->expectExceptionCode(500);
        $actual = $this->hydrator->hydrate($spec, ['foo' => []]);
    }

    /**
     * Test middleware and a resolver together.
     */
    public function testMiddlewareAndParams() {
        $spec = [
            DataHydrator::KEY_TYPE => 'param',
            'ref' => 'foo',
            DataHydrator::KEY_MIDDLEWARE => [
                [
                    DataHydrator::KEY_TYPE => 'transform',
                    'transform' => '/foo',
                ],
            ],
        ];
        $actual = $this->hydrator->hydrate($spec, ['foo' => ['foo' => 'bar']]);
        $this->assertSame('bar', $actual);
    }
}
