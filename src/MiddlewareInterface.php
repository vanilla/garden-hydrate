<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

use Garden\Hydrate\DataResolverInterface;

/**
 * Adds support for middleware on a data resolver.
 */
interface MiddlewareInterface {
    /**
     * Process the middleware.
     *
     * @param array $data The data being resolved.
     * @param array $params Additional parameters being passed to the hydrator.
     * @param DataResolverInterface $next The next resolver in the chain.
     * @return mixed Returns whatever the middleware or hydrator wants to return.
     */
    public function process(array $data, array $params, DataResolverInterface $next);

    /**
     * Validate the middleware data against defined schema
     *
     * @param array $data
     * @return mixed
     */
    public function validate(array $data);
}
