<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Exception\ResolverNotFoundException;
use Garden\Hydrate\NullExceptionHandler;
use Garden\Hydrate\Tests\Fixtures\NestedObjSchemaResolver;
use PHPUnit\Framework\TestCase;
use Garden\Hydrate\Tests\Fixtures\ExceptionThrowerResolver;
use Garden\Hydrate\Tests\Fixtures\TestExceptionHandler;
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
            ->addResolver(new ExceptionThrowerResolver())
            ->addResolver(new TestStringResolver('a'));
    }

    /**
     * A basic array with no type should return a copy of itself.
     */
    public function testNothing(): void {
        $arr = ['foo' => 'bar'];

        $actual = $this->hydrator->resolve($arr, []);
        $this->assertEquals($arr, $actual);
    }

    /**
     * The spec should be able to return a single context element.
     */
    public function testRootParam(): void {
        $spec = ['$hydrate' => 'param', 'ref' => 'foo'];
        $actual = $this->hydrator->resolve($spec, ['foo' => 'bar']);
        $this->assertSame('bar', $actual);
    }

    /**
     * I should be able to resolve a nested array.
     */
    public function testNestedType(): void {
        $spec = ['foo' => ['$hydrate' => 'param', 'ref' => 'foo']];
        $actual = $this->hydrator->resolve($spec, ['foo' => 'bar']);
        $this->assertSame(['foo' => 'bar'], $actual);
    }

    /**
     * I should be able to resolve arguments to a parent resolver.
     */
    public function testRecursiveResolution(): void {
        $spec = ['$hydrate' => 'param', 'ref' => ['$hydrate' => 'param', 'ref' => 'foo']];
        $actual = $this->hydrator->resolve($spec, ['foo' => 'bar', 'bar' => 'baz']);
        $this->assertSame('baz', $actual);
    }

    /**
     * A reference should be able to reference the root data.
     */
    public function testRootRef(): void {
        $spec = ['foo' => 'bar', 'baz' => ['$hydrate' => 'ref', 'ref' => '/foo']];
        $expected = ['foo' => 'bar', 'baz' => 'bar'];

        $actual = $this->hydrator->resolve($spec, []);
        $this->assertSame($expected, $actual);
    }

    /**
     * The exception handler should be able to catch an exception at any nesting level.
     */
    public function testExceptionBoundary(): void {
        $spec = [
            [
                '$hydrate' => 'exception',
                'message' => 'outer',
                'throw' => false,
                'nest' => [
                    '$hydrate' => 'exception',
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

        $actual = $this->hydrator->resolve($spec, []);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test unregistering a resolver.
     */
    public function testUnregisterResolver() {
        $actual = $this->hydrator->resolve(['$hydrate' => 'str'], []);
        $this->assertSame(['str' => 'a'], $actual);

        $r = $this->hydrator->removeResolver('str');
        $this->assertSame($this->hydrator, $r);
        $this->expectException(ResolverNotFoundException::class);
        $this->expectExceptionCode(404);
        $actual = $this->hydrator->resolve(['$hydrate' => 'str']);
    }

    /**
     * Test the exception handler accessors.
     */
    public function testExceptionHandlerAccessors() {
        $handler1 = $this->hydrator->getExceptionHandler();
        $handler2 = new NullExceptionHandler();
        $this->assertNotSame($handler1, $handler2);
        $r = $this->hydrator->setExceptionHandler($handler2);
        $this->assertSame($handler2, $this->hydrator->getExceptionHandler());
        $this->assertSame($r, $this->hydrator);
    }

    /**
     * The default exception handler should just throw exceptions.
     */
    public function testDefaultNullExceptionHandler() {
        $hydrator = new DataHydrator();

        $this->expectException(ResolverNotFoundException::class);
        $actual = $hydrator->resolve(['$hydrate' => 'foo']);
    }

    /**
     * Test a basic data transform integration.
     */
    public function testTransformMiddlewareIntegration() {
        $spec = [
            '$hydrate' => 'literal', 'data' => ['a' => ['foo' => 'bar']],
            DataHydrator::KEY_MIDDLEWARE => [
                'transform' => ['baz' => '/a/foo'],
            ],
        ];
        $expected = ['baz' => 'bar'];
        $actual = $this->hydrator->resolve($spec);
        $this->assertSame($expected, $actual);
    }

    /**
     * The `'$middleware'` key should always be removed.
     */
    public function testJustMiddlewareRemoval() {
        $spec = ['foo' => 'bar', DataHydrator::KEY_MIDDLEWARE => []];
        $actual = $this->hydrator->resolve($spec);
        $this->assertSame(['foo' => 'bar'], $actual);
    }

    /**
     * Test a middleware with no resolver.
     */
    public function testTransformMiddlewareNoResolver() {
        $spec = [
            'a' => ['foo' => 'bar'],
            DataHydrator::KEY_MIDDLEWARE => [
                'transform' => ['baz' => '/a/foo'],
            ],
        ];
        $expected = ['baz' => 'bar'];
        $actual = $this->hydrator->resolve($spec);
        $this->assertSame($expected, $actual);
    }

    /**
     * Test a happy path for the `sprintf` type.
     */
    public function testSprintf() {
        $spec = [
            DataHydrator::KEY_HYDRATE => 'sprintf',
            'format' => 'Hello %s',
            'args' => [
                'foo',
            ],
        ];
        $actual = $this->hydrator->resolve($spec);
        $this->assertSame('Hello foo', $actual);
    }

    /**
     * Test that a basic set of resolvers are registered by default.
     */
    public function testDefaultResolvers(): void {
        $hydrator = new DataHydrator();

        $this->assertTrue($hydrator->hasResolver('ref'));
        $this->assertTrue($hydrator->hasResolver('param'));
        $this->assertTrue($hydrator->hasResolver('literal'));
        $this->assertTrue($hydrator->hasResolver('sprintf'));
    }

    /**
     * Validate that data resolver schemas are applied.
     */
    public function testE2ESchemaValidation() {
        $hydrator = new DataHydrator();
        $hydrator->addResolver(new NestedObjSchemaResolver());
        $input = [
            '$hydrate' => 'nestedObjSchema',
            'nested' => [
                'foo' => 'hello foo',
                'bar' => 'hello bar',
                'notInSchema' => 'not in schema',
            ],
        ];

        // Fields are stripped.
        $expected = [
            'nested' => [
                'foo' => 'hello foo',
                'bar' => 'hello bar',
            ]
        ];

        $actual = $hydrator->resolve($input, []);
        $this->assertSame($expected, $actual);

        // Should throw validation errors (that would get caught by the exception handler if there was one).
        $this->expectExceptionMessage("nested is required");
        $input = ['$hydrate' => 'nestedObjSchema'];
        $hydrator->resolve($input);
    }
}
