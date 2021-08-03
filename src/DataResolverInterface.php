<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

/**
 * An interface for data resolvers.
 *
 * Data resolvers are responsible for hydrating the data of a certain '$hydrate' key.
 */
interface DataResolverInterface {
    /**
     * Resolve the data at a node.
     *
     * @param array $data The data to resolve.
     * @param array $params Any additional global parameters passed to the hyrdrator.
     * @return mixed The resolver can return whatever it wants.
     */
    public function resolve(array $data, array $params = []);

    /**
     * Get the type of the resolver.
     *
     * @return string
     */
    public function getType(): string;
}
