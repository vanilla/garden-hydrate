<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\Resolvers\FunctionResolver;
use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the `ReflectedFunctionResolver` class.
 */
class FunctionResolverTest extends TestCase {
    /**
     * Test basic reflection with the `implode()` function.
     */
    public function testImplode(): void {
        $resolver = new FunctionResolver(function (string $glue, array $pieces) {
            return implode($glue, $pieces);
        });

        $actual = $resolver->resolve(['glue' => ',', 'pieces' => ['a', 'b']], []);
        $this->assertSame('a,b', $actual);
    }

    /**
     * Test a function with a default parameter.
     */
    public function testParamDefault(): void {
        $resolver = new FunctionResolver(function (string $a, string $b = 'b') {
            return $a.$b;
        });

        $actual = $resolver->resolve(['a' => 'a'], []);
        $this->assertSame('ab', $actual);
    }

    /**
     * Test some specific type hints.
     *
     * @param mixed $s
     * @param mixed $b
     * @param mixed $i
     * @param mixed $f
     * @param mixed $a
     * @param string $exception
     * @dataProvider provideTypeHindTests
     */
    public function testTypeHints($s, $b, $i, $f, $a, string $exception = ''): void {
        $resolver = new FunctionResolver(function (string $s, bool $b, int $i, float $f, array $a) {
            return 'foo';
        });

        if ($exception) {
            $this->expectException(ValidationException::class);
        }
        $actual = $resolver->resolve(['s' => $s, 'b' => $b, 'i' => $i, 'f' => $f, 'a' => $a], []);
        $this->assertSame('foo', $actual);
    }

    /**
     * Provide type hit tests.
     *
     * @return array
     */
    public function provideTypeHindTests(): array {
        $c = ['s', true, 123, 12.3, [1, 2, 4]];

        $r = [
            $c,
            array_replace($c, [0 => [], 5 => 's']),
            array_replace($c, [1 => [], 5 => 'b']),
            array_replace($c, [2 => [], 5 => 'i']),
            array_replace($c, [3 => [], 5 => 'f']),
            array_replace($c, [4 => 'foo', 5 => 'a']),
            array_replace($c, [4 => ['foo' => 'bar']]),
        ];

        return $r;
    }

    /**
     * A nullable parameter should accept a null value.
     */
    public function testNullableParameter(): void {
        $resolver = new FunctionResolver(function (string $a, ?string $b) {
            return $a.$b;
        });

        $actual = $resolver->resolve(['a' => 'a', 'b' => null], []);
        $this->assertSame('a', $actual);
    }

    /**
     * An invalid argument should throw a validation exception.
     */
    public function testValidationException(): void {
        $resolver = new FunctionResolver(function (string $a) {
            return $a;
        });

        $this->expectException(ValidationException::class);
        $resolver->resolve(['a' => ['a']], []);
    }

    /**
     * A variadic function can specify the variadic part
     */
    public function testVariadicFunction(): void {
        $resolver = new FunctionResolver(function (string $glue, ...$args) {
            return implode($glue, $args);
        });

        $actual = $resolver->resolve(['glue' => ',', 'args' => ['a', 'b']], []);
        $this->assertSame('a,b', $actual);
    }

    /**
     * Test with an instance method.
     */
    public function testInstanceMethod(): void {
        $resolver = new FunctionResolver([$this, 'instanceMethod']);
        $actual = $resolver->resolve([], []);
        $this->assertSame('foo', $actual);
    }

    /**
     * A test instance method.
     *
     * @return string
     */
    public function instanceMethod(): string {
        return 'foo';
    }

    /**
     * Test reflecting a static method.
     */
    public function testStaticMethod(): void {
        $resolver = new FunctionResolver([self::class, 'staticMethod']);
        $actual = $resolver->resolve([], []);
        $this->assertSame('foo', $actual);
    }

    /**
     * A test static method.
     *
     * @return string
     */
    public static function staticMethod(): string {
        return 'foo';
    }

    /**
     * Variadic arguments must be an array.
     */
    public function testInvalidVariadic(): void {
        $resolver = new FunctionResolver(function (...$args) {
            return $args;
        });
        $this->expectException(ValidationException::class);
        $actual = $resolver->resolve(['args' => 'foo'], []);
    }

    /**
     * A variadic function should work with no variadic args passed.
     */
    public function testVariadicNoArgs(): void {
        $resolver = new FunctionResolver(function (...$args) {
            return $args;
        });
        $actual = $resolver->resolve([], []);
        $this->assertSame([], $actual);
    }

    /**
     * A function parameter with no type hint should accept multiple types.
     */
    public function testFunctionParamNoType() {
        $resolver = new FunctionResolver(function ($a) {
            return $a;
        });

        $actual = $resolver->resolve(['a' => 'foo'], []);
        $this->assertSame('foo', $actual);

        $expected = new \ArrayObject(['foo' => 'bar']);
        $actual = $resolver->resolve(['a' => $expected], []);
        $this->assertSame($expected, $actual);
    }
}
