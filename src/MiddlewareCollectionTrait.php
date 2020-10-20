<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

trait MiddlewareCollectionTrait {
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @param MiddlewareInterface $middleware
     */
    public function addMiddleware(MiddlewareInterface $middleware): void {
        $this->middlewares[] = $middleware;
    }

    /**
     *
     *
     * @param $middleware
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

    public function process(array $data, array $params, DataResolverInterface $next) {
        $resolver = DataHydrator::makeMiddlewareResolver($this->middlewares, $next);
        $r = $resolver->resolve($data, $params);
        return $r;
    }
}
