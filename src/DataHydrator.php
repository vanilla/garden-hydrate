<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

use Exception;
use Garden\Hydrate\Exception\ResolverNotFoundException;
use Garden\Hydrate\Middleware\TransformMiddleware;
use Garden\Hydrate\Resolvers\LiteralResolver;
use Garden\Hydrate\Resolvers\ParamResolver;
use Garden\Hydrate\Resolvers\RefResolver;
use Garden\Hydrate\Resolvers\SprintfResolver;

/**
 * Allows data to by hydrated based on a spec that can include data resolvers or literal data.
 */
class DataHydrator implements DataResolverInterface {
    use MiddlewareCollectionTrait;

    public const KEY_HYDRATE = '@hydrate';
    public const KEY_MIDDLEWARE = '@middleware';
    public const KEY_MIDDLEWARE_TYPE = 'type';
    public const KEY_ROOT = '@root';

    /**
     * @var DataResolverInterface[]
     */
    private $resolvers = [];

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares = [];

    /**
     * @var ExceptionHandlerInterface
     */
    private $exceptionHandler;


    /**
     * @var DataResolverInterface A combination of the middleware and inner resolver for resolving nodes.
     */
    private $resolver;

    /**
     * DataHydrator constructor.
     */
    public function __construct() {
        $this->setExceptionHandler(new NullExceptionHandler());

        $this->addResolver('literal', new LiteralResolver());
        $this->addResolver('param', new ParamResolver());
        $this->addResolver('ref', new RefResolver());
        $this->addResolver('sprintf', new SprintfResolver());

        $this->addMiddleware(new TransformMiddleware());
    }

    /**
     * Add a new resolver.
     *
     * @param string $type
     * @param DataResolverInterface $resolver
     * @return $this
     */
    public function addResolver(string $type, DataResolverInterface $resolver) {
        $this->resolvers[$type] = $resolver;
        return $this;
    }

    /**
     * Remove an existing resolver.
     *
     * @param string $type
     * @return $this
     */
    public function removeResolver(string $type) {
        unset($this->resolvers[$type]);
        return $this;
    }

    /**
     * Returns whether or not a resolver is registered.
     *
     * @param string $type
     * @return bool
     */
    public function hasResolver(string $type): bool {
        return isset($this->resolvers[$type]);
    }

    /**
     * Hydrate a data specification.
     *
     * This is the main
     *
     * @param array $data The specification that defines the data.
     * @param array $params Additional contextual data.
     * @return mixed Returns the hydrated data.
     */
    public function resolve(array $data, array $params = []) {
        $params[self::KEY_ROOT] = $data;

        $this->resolver = self::makeMiddlewareResolver(
            $this->middlewares,
            self::makeResolver(function (array $data, array $params) {
                return $this->resolveNode($data, $params);
            })
        );
        $result = $this->resolveInternal($data, $params);

        return $result;
    }

    /**
     * Internal implementation of the hydration.
     *
     * @param array $data The data to hydrate.
     * @param array $params Additional contextual data.
     * @return mixed Returns the mixed data.
     */
    private function resolveInternal(array $data, array $params) {
        $result = [];
        try {
            $result = $this->resolveChildren($data, $params);
            $result = $this->resolver->resolve($result, $params);
        } catch (Exception $ex) {
            $result = $this->exceptionHandler->handleException($ex, $result, $params);
        }

        return $result;
    }

    /**
     * Resolve the data at a node.
     *
     * @param array $data A data node with all children resolved.
     * @param array $params
     * @return array|mixed
     * @throws ResolverNotFoundException Throws an exception when there isn't a resolver registered.
     */
    private function resolveNode(array $data, array $params) {
        if (isset($data[self::KEY_HYDRATE])) {
            $type = $data[self::KEY_HYDRATE];
            $resolver = $this->getResolver($type);

            $data = $resolver->resolve($data, $params);
        }
        if (is_array($data)) {
            unset($data[self::KEY_MIDDLEWARE]);
        }
        return $data;
    }

    /**
     * Get the exception handler.
     *
     * @return ExceptionHandlerInterface
     */
    public function getExceptionHandler(): ExceptionHandlerInterface {
        return $this->exceptionHandler;
    }

    /**
     * Set the exception handler.
     *
     * @param ExceptionHandlerInterface $exceptionHandler
     * @return self
     */
    public function setExceptionHandler(ExceptionHandlerInterface $exceptionHandler): self {
        $this->exceptionHandler = $exceptionHandler;
        return $this;
    }

    /**
     * Get a resolver from the registered resolvers.
     *
     * @param string $type
     * @return DataResolverInterface
     * @throws ResolverNotFoundException Throws an exception if the resolver isn't registered.
     */
    private function getResolver(string $type): DataResolverInterface {
        if (!$this->hasResolver($type)) {
            throw new ResolverNotFoundException("Resolver not registered: $type");
        }
        $resolver = $this->resolvers[$type];
        return $resolver;
    }

    /**
     * Resolve the child nodes of the current node.
     *
     * @param array $data
     * @param array $params
     * @return array
     */
    private function resolveChildren(array $data, array $params): array {
        $result = [];
        try {
            // First resolve as much static data as possible in case there are exceptions.
            $recurse = [];
            foreach ($data as $key => $value) {
                if (in_array($key, [self::KEY_HYDRATE, self::KEY_MIDDLEWARE], true) || !is_array($value)) {
                    $result[$key] = $value;
                } else {
                    $result[$key] = null; // placeholder to maintain order
                    $recurse[$key] = $value;
                }
            }
            // Handle the special case for a literal value.
            if (isset($data[self::KEY_HYDRATE]) && $data[self::KEY_HYDRATE] === 'literal' && isset($data['data'])) {
                $result['data'] = $data['data'];
                unset($recurse['data']);
            }

            // Resolve any recursive items.
            foreach ($recurse as $key => $value) {
                $result[$key] = $this->resolveInternal($value, $params);
            }
        } catch (Exception $ex) {
            $result = $this->exceptionHandler->handleException($ex, $result, $params);
        }
        return $result;
    }

    /**
     * Make a data resolver out of a collection of middleware and an inner resolver.
     *
     * @param MiddlewareInterface[] $middlewares
     * @param DataResolverInterface $inner
     * @return DataResolverInterface
     */
    public static function makeMiddlewareResolver(array $middlewares, DataResolverInterface $inner): DataResolverInterface {
        $resolver = $inner;
        foreach ($middlewares as $middleware) {
            $resolver = new class($middleware, $resolver) implements DataResolverInterface {
                /**
                 * @var MiddlewareInterface
                 */
                private $middleware;

                /**
                 * @var DataResolverInterface
                 */
                private $next;

                /**
                 * Construct a pointer for a middleware.
                 *
                 * @param MiddlewareInterface $middleware
                 * @param DataResolverInterface $next
                 */
                public function __construct(MiddlewareInterface $middleware, DataResolverInterface $next) {
                    $this->middleware = $middleware;
                    $this->next = $next;
                }

                /**
                 * Resolve the middleware against the next resolver.
                 *
                 * @param array $data
                 * @param array $params
                 * @return mixed
                 */
                public function resolve(array $data, array $params = []) {
                    $r = $this->middleware->process($data, $params, $this->next);
                    return $r;
                }
            };
        }
        return $resolver;
    }

    /**
     * Make a data resolver out of a function that implements the `resolve()` method.
     *
     * This is a convenience function to avoid having to create a new class everytime you want a resolver.
     *
     * @param callable $resolver
     * @return DataResolverInterface
     */
    public static function makeResolver(callable $resolver): DataResolverInterface {
        return new class($resolver) implements DataResolverInterface {
            private $resolver;

            /**
             * Constructor.
             *
             * @param callable $resolver
             */
            public function __construct(callable $resolver) {
                $this->resolver = $resolver;
            }

            /**
             * {@inheritDoc}
             */
            public function resolve(array $data, array $params = []) {
                $r = ($this->resolver)($data, $params);
                return $r;
            }
        };
    }
}
