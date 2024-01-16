<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests;

use Garden\Hydrate\Resolvers\SprintfResolver;
use Garden\Schema\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the `SprintfResolver` class.
 */
class SprintfResolverTest extends TestCase {
    /**
     * Format is required.
     */
    public function testNoFormat() {
        $resolver = new SprintfResolver();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('format is required.');
        $actual = $resolver->resolve([], []);
    }

    /**
     * Format must be a string.
     */
    public function testBafFormat() {
        $resolver = new SprintfResolver();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('format: The value is not a valid string.');
        $actual = $resolver->resolve(['format' => []], []);
    }

    /**
     * Args must be an array.
     */
    public function testBadArgs() {
        $resolver = new SprintfResolver();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('args');
        $actual = $resolver->resolve(['format' => 'foo', 'args' => 'foo'], []);
    }
}
