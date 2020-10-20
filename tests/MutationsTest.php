<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */
namespace Garden\Hydrate\Tests;

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
            ->addResolver('exception', new ExceptionThrowerResolver())
            ->addResolver('str', new TestStringResolver('a'));
    }

    /**
     * The `'@hydrate'` field shouldn't expand.
     */
    public function testStaticTypeField() {
        $spec = [DataHydrator::KEY_HYDRATE => [DataHydrator::KEY_HYDRATE => 'param', 'ref' => 'foo']];
        $this->expectException(\Throwable::class);
        $actual = $this->hydrator->hydrate($spec, ['foo' => 'literal']);
    }

    /**
     * Test middleware and a resolver together.
     */
    public function testMiddlewareAndParams() {
        $spec = [
            DataHydrator::KEY_HYDRATE => 'param',
            'ref' => 'foo',
            DataHydrator::KEY_MIDDLEWARE => [
                'transform' => '/foo',
            ],
        ];
        $actual = $this->hydrator->resolve($spec, ['foo' => ['foo' => 'bar']]);
        $this->assertSame('bar', $actual);
    }
}
