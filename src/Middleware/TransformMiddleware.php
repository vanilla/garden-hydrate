<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Middleware;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\MiddlewareInterface;
use Garden\JSON\Transformer;

/**
 * Middleware that transforms the data after it has been resolved.
 */
class TransformMiddleware implements MiddlewareInterface {
    /**
     * {@inheritDoc}
     */
    public function process(array $data, array $params, DataResolverInterface $next) {
        $data = $next->resolve($data, $params);

        $transform = $params[DataHydrator::KEY_MIDDLEWARE]['transform'];
        $transformer = new Transformer($transform);

        $result = $transformer->transform($data);
        return $result;
    }
}
