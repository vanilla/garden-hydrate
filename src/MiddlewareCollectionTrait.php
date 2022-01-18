<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

use Garden\Hydrate\Middleware\AbstractMiddleware;

/**
 * Apply this to a class to add support for collecting middleware.
 */
trait MiddlewareCollectionTrait {
    /**
     * @var AbstractMiddleware[]
     */
    private $middlewares = [];

    /**
     * @param AbstractMiddleware $middleware
     */
    public function addMiddleware(AbstractMiddleware $middleware): void {
        $this->middlewares[] = $middleware;
    }

    /**
     * Whether or not a middleware exists in the collection.
     *
     * @param string|AbstractMiddleware $middleware The class name of a middleware or a specific instance to look up.
     * @return bool
     */
    public function hasMiddleware($middleware): bool {
        foreach ($this->middlewares as $m) {
            if (is_string($middleware) && is_a($m, $middleware)) {
                return true;
            } elseif ($m === $middleware) {
                return true;
            }
        }
        return false;
    }

    /**
     * Process all of the middleware in the collection.
     *
     * @param array $data The data node being resolved.
     * @param array $params Hyrdration parameters.
     * @param DataResolverInterface $next
     * @return mixed
     */
    public function process(array $data, array $params, DataResolverInterface $next) {
        $resolver = DataHydrator::makeMiddlewareResolver($this->middlewares, $next);
        $r = $resolver->resolve($data, $params);
        return $r;
    }
}
