<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Exception\MiddlewareNotFoundException;
use Garden\Hydrate\Exception\ResolverNotFoundException;
use Garden\Hydrate\NullExceptionHandler;
use PHPUnit\Framework\TestCase;
use Garden\Hydrate\Tests\Fixtures\ExceptionThrowerResolver;
use Garden\Hydrate\Tests\Fixtures\TestExceptionHandler;
use Garden\Hydrate\Tests\Fixtures\TestStringMiddleware;
use Garden\Hydrate\Tests\Fixtures\TestStringResolver;

/**
 * Tests for the `DataHydrator` class.
 */
class DataHydratorTest extends TestCase {
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
     * A basic array with no type should return a copy of itself.
     */
    public function testNothing(): void {
        $arr = ['foo' => 'bar'];

        $actual = $this->hydrator->hydrate($arr, []);
        $this->assertEquals($arr, $actual);
    }

    /**
     * The spec should be able to return a single context element.
     */
    public function testRootParam(): void {
        $spec = ['$type' => 'param', 'ref' => 'foo'];
        $actual = $this->hydrator->hydrate($spec, ['foo' => 'bar']);
        $this->assertSame('bar', $actual);
    }

    /**
     * I should be able to resolve a nested array.
     */
    public function testNestedType(): void {
        $spec = ['foo' => ['$type' => 'param', 'ref' => 'foo']];
        $actual = $this->hydrator->hydrate($spec, ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $actual);
    }

    /**
     * I should be able to resolve arguments to a parent resolver.
     */
    public function testRecursiveResolution(): void {
        $spec = ['$type' => 'param', 'ref' => ['$type' => 'param', 'ref' => 'foo']];
        $actual = $this->hydrator->hydrate($spec, ['foo' => 'bar', 'bar' => 'baz']);
        $this->assertSame('baz', $actual);
    }

    /**
     * A reference should be able to reference the root data.
     */
    public function testRootRef(): void {
        $spec = ['foo' => 'bar', 'baz' => ['$type' => 'ref', 'ref' => '/foo']];
        $expected = ['foo' => 'bar', 'baz' => 'bar'];

        $actual = $this->hydrator->hydrate($spec);
        $this->assertSame($expected, $actual);
    }

    /**
     * The exception handler should be able to catch an exception at any nesting level.
     */
    public function testExceptionBoundary(): void {
        $spec = [
            [
                '$type' => 'exception',
                'message' => 'outer',
                'throw' => false,
                'nest' => [
                    '$type' => 'exception',
                    'message' => 'inner'
                ],
            ],
            [
                'foo' => 'bar',
            ]
        ];

        $expected = [
            [
                'exception' => true,
                'class' => 'Exception',
                'message' => 'inner',
                'code' => 500,
            ],
            [
                'foo' => 'bar',
            ],
        ];

        $actual = $this->hydrator->hydrate($spec, []);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test unregistering a resolver.
     */
    public function testUnregisterResolver() {
        $actual = $this->hydrator->hydrate(['$type' => 'str']);
        $this->assertSame(['str' => 'a'], $actual);

        $this->hydrator->unregisterResolver('str');
        $this->expectException(ResolverNotFoundException::class);
        $actual = $this->hydrator->hydrate(['$type' => 'str']);
    }

    /**
     * Test the exception handler accessors.
     */
    public function testExceptionHandlerAccessors() {
        $handler1 = $this->hydrator->getExceptionHandler();
        $handler2 = new NullExceptionHandler();
        $this->assertNotSame($handler1, $handler2);
        $this->hydrator->setExceptionHandler($handler2);
        $this->assertSame($handler2, $this->hydrator->getExceptionHandler());
    }

    /**
     * Test the middleware accessors.
     */
    public function testMiddlewareAccessors() {
        $mw = new TestStringMiddleware('a');
        $this->hydrator->registerMiddleware('strA', $mw);
        $this->assertTrue($this->hydrator->isMiddlewareRegistered('strA'));

        $this->hydrator->unregisterMiddleware('strA');
        $this->assertFalse($this->hydrator->isMiddlewareRegistered('strA'));
    }

    /**
     * The default exception handler should just throw exceptions.
     */
    public function testDefaultNullExceptionHandler() {
        $hydrator = new DataHydrator();

        $this->expectException(ResolverNotFoundException::class);
        $actual = $hydrator->hydrate(['$type' => 'foo']);
    }

    /**
     * Test a basic data transform integration.
     */
    public function testTransformMiddlewareIntegration() {
        $spec = ['$type' => 'literal', 'data' => ['a' => ['foo' => 'bar']], DataHydrator::KEY_MIDDLEWARE => [
                [DataHydrator::KEY_TYPE => 'transform', 'transform' => ['baz' => '/a/foo']],
            ]
        ];
        $expected = ['baz' => 'bar'];
        $actual = $this->hydrator->hydrate($spec);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test an invalid middleware.
     */
    public function testInvalidMiddleware() {
        $spec = [
            '$type' => 'literal',
            'data' => ['a' => ['foo' => 'bar']],
            DataHydrator::KEY_MIDDLEWARE => [
                [DataHydrator::KEY_TYPE => 'foo'],
            ]
        ];
        $this->expectException(MiddlewareNotFoundException::class);
        $actual = $this->hydrator->hydrate($spec);
    }

    /**
     * The `'$middleware'` key should always be removed.
     */
    public function testJustMiddlewareRemoval() {
        $spec = ['foo' => 'bar', DataHydrator::KEY_MIDDLEWARE => []];
        $actual = $this->hydrator->hydrate($spec);
        $this->assertSame(['foo' => 'bar'], $actual);
    }

    /**
     * Test a middleware with no resolver.
     */
    public function testTransformMiddlewareNoResolver() {
        $spec = [
            'a' => ['foo' => 'bar'],
            DataHydrator::KEY_MIDDLEWARE => [
                [DataHydrator::KEY_TYPE => 'transform', 'transform' => ['baz' => '/a/foo']],
            ]
        ];
        $expected = ['baz' => 'bar'];
        $actual = $this->hydrator->hydrate($spec);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test a happy path for the `sprintf` type.
     */
    public function testSprintf() {
        $spec = [
            DataHydrator::KEY_TYPE => 'sprintf',
            'format' => 'Hello %s',
            'args' => [
                'foo',
            ],
        ];
        $actual = $this->hydrator->hydrate($spec);
        $this->assertSame('Hello foo', $actual);
    }
}
