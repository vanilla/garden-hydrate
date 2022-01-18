<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

use Garden\Schema\Schema;

/**
 * Adds support for middleware on a data resolver.
 */
interface MiddlewareInterface {
    /**
     * Process the middleware.
     *
     * @param array $nodeData The data being resolved.
     * @param array $hydrateParams Additional parameters being passed to the hydrator.
     * @param DataResolverInterface $next The next resolver in the chain.
     * @return mixed Returns whatever the middleware or hydrator wants to return.
     */
    public function process(array $nodeData, array $hydrateParams, DataResolverInterface $next);

    /**
     * Validate the middleware data against defined schema
     *
     * @param array $middlewareParams
     * @return mixed
     */
    public function validateParams(array $middlewareParams);

    /**
     * Get the type of the resolver.
     *
     * @return string
     */
    public function getType(): string;


    /**
     * @return Schema|null
     */
    public function getSchema(): ?Schema;
}
