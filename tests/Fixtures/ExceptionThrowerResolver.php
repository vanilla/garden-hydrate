<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Fixtures;

use Garden\Hydrate\DataResolverInterface;

/**
 * A data resolver that just throws exceptions.
 */
class ExceptionThrowerResolver implements DataResolverInterface {
    /**
     * {@inheritDoc}
     */
    public function resolve(array $data, array $params = []) {
        throw new \Exception($data['message'], $data['code'] ?? 500);
    }
}
