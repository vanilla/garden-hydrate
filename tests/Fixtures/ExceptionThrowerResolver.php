<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Fixtures;

use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\Resolvers\AbstractDataResolver;

/**
 * A data resolver that just throws exceptions.
 */
class ExceptionThrowerResolver extends AbstractDataResolver {
    /**
     * @inheritDoc
     */
    public function resolveInternal(array $data, array $params = []) {
        throw new \Exception($data['message'], $data['code'] ?? 500);
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return 'exception';
    }
}
