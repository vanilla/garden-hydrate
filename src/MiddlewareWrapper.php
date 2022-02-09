<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

/**
 * A wrapper for middleware that turns it into a data resolver.
 *
 * This class is used internally so that middleware can be properly chained.
 */
class MiddlewareWrapper implements DataResolverInterface {
    /**
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * @var DataResolverInterface
     */
    private $resolver;

    /**
     * @var array
     */
    private $params;

    /**
     * MiddlewareWrapper constructor.
     *
     * @param MiddlewareInterface $middleware The middleware to call.
     * @param DataResolverInterface $resolver The next resolver in the chain.
     * @param array $params An array of parameters for the middleware itself.
     */
    public function __construct(MiddlewareInterface $middleware, DataResolverInterface $resolver, array $params = []) {
        $this->middleware = $middleware;
        $this->resolver = $resolver;
        $this->params = $params;
    }

    /**
     * Resolve the middleware.
     *
     * @param array $data
     * @param array $params
     * @return mixed
     */
    public function resolve(array $data, array $params = []) {
        $allParams = array_replace($params, [DataHydrator::KEY_MIDDLEWARE => $this->params]);

        $r = $this->middleware->process($data, $allParams, $this->resolver);
        return $r;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return 'middlewareWrapper';
    }
}
