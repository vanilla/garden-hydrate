<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Middleware;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\MiddlewareInterface;

/**
 * A useful base class for middleware.
 */
abstract class AbstractMiddleware implements MiddlewareInterface {

    /**
     * Default process implementation
     * - Validates middlewares with the schema.
     * - Does nothing if there is no middleware matching our key.
     *
     * @param array $nodeData
     * @param array $hydrateParams
     * @param DataResolverInterface $next
     *
     * @return mixed
     */
    public function process(array $nodeData, array $hydrateParams, DataResolverInterface $next) {
        $middlewareParams = $this->getMiddlewareParams($nodeData);
        if ($middlewareParams === null) {
            // Do nothing.
            $data = $next->resolve($nodeData, $hydrateParams);
            return $data;
        }

        $processed = $this->processInternal($nodeData, $middlewareParams, $hydrateParams, $next);
        return $processed;
    }

    /**
     * Middleware implementation.
     *
     * @param array $nodeData The data from the node.
     * @param array $middlewareParams Validated middleware parameters.
     * @param array $hydrateParams Parameters passed into the hydrator.
     * @param DataResolverInterface $next Data resolver to continue the middleware.
     *
     * @return mixed The resolved data.
     */
    abstract protected function processInternal(
        array $nodeData,
        array $middlewareParams,
        array $hydrateParams,
        DataResolverInterface $next
    );


    /**
     * {@inheritDoc}
     */
    public function validateParams(array $middlewareParams): array {
        $schema = $this->getSchema();
        if ($schema !== null) {
            return $schema->validate($middlewareParams);
        } else {
            return $middlewareParams;
        }
    }

    /**
     * Get the validated middleware params.
     *
     * @param mixed $nodeData The data of the node.
     *
     * @return array|null
     */
    public function getMiddlewareParams($nodeData): ?array {
        $rawMiddlewareParams = $nodeData[DataHydrator::KEY_MIDDLEWARE][$this->getType()] ?? null;
        if ($rawMiddlewareParams === null) {
            return null;
        }
        $validatedParams = $this->validateParams($rawMiddlewareParams);
        return $validatedParams;
    }
}
