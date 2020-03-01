<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Fixtures;

use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\MiddlewareInterface;

/**
 * A test middleware that adds a basic string to the data.
 */
class TestStringMiddleware implements MiddlewareInterface {
    /**
     * @var string
     */
    private $str = '';

    /**
     * TestMiddleware constructor.
     * @param string $str
     */
    public function __construct(string $str) {
        $this->str = $str;
    }


    /**
     * {@inheritDoc}
     */
    public function process(array $data, array $params, DataResolverInterface $next) {
        $data['str'] = ($data['str'] ?? '').$this->str;
        $data = $next->resolve($data, $params);
        $data['str'] = ($data['str'] ?? '').$this->str;

        return $data;
    }
}
