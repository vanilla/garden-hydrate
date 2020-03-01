<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Fixtures;

use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Hydrate\DataHydrator;

/**
 * A test resolver that adds a string to the data.
 */
class TestStringResolver extends AbstractDataResolver {
    /**
     * @var string
     */
    private $str = '';

    /**
     * TestStringResolver constructor.
     *
     * @param string $str
     */
    public function __construct(string $str) {
        $this->str = $str;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveInternal(array $data, array $params) {
        unset($data[DataHydrator::KEY_TYPE]);
        $data['str'] = ($data['str'] ?? '').$this->str;

        return $data;
    }
}
