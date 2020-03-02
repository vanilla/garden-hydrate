<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

use Garden\Hydrate\Exception\MiddlewareNotFoundException;
use Garden\Hydrate\Exception\ResolverNotFoundException;
use Garden\Hydrate\Middleware\TransformMiddleware;
use Garden\Hydrate\Resolvers\FunctionResolver;
use Garden\Hydrate\Resolvers\LiteralResolver;
use Garden\Hydrate\Resolvers\ParamResolver;
use Garden\Hydrate\Resolvers\RefResolver;
use Garden\Hydrate\Resolvers\SprintfResolver;

/**
 * Allows data to by hydrated based on a spec that can include data resolvers or literal data.
 */
class DataHydrator implements DataResolverInterface {
    public const KEY_TYPE = '$type';
    public const KEY_MIDDLEWARE = '$middleware';
    public const KEY_ROOT = '$root';

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
     * DataHydrator constructor.
     */
    public function __construct() {
        $this->setExceptionHandler(new NullExceptionHandler());

        $this->registerResolver('ref', new RefResolver());
        $this->registerResolver('param', new ParamResolver());
        $this->registerResolver('literal', new LiteralResolver());
        $this->registerResolver('sprintf', new SprintfResolver());

        $this->registerMiddleware('transform', new TransformMiddleware());
    }

    /**
     * Add a new resolver.
     *
     * @param string $type
     * @param DataResolverInterface $resolver
     * @return $this
     */
    public function registerResolver(string $type, DataResolverInterface $resolver) {
        $this->resolvers[$type] = $resolver;
        return $this;
    }

    /**
     * Remove an existing resolver.
     *
     * @param string $type
     * @return $this
     */
    public function unregisterResolver(string $type) {
        unset($this->resolvers[$type]);
        return $this;
    }

    /**
     * Returns whether or not a resolver is registered.
     *
     * @param string $type
     * @return bool
     */
    public function isResolverRegistered(string $type): bool {
        return isset($this->resolvers[$type]);
    }

    /**
     * Register a middleware that can be placed on a resolver.
     *
     * @param string $name
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function registerMiddleware(string $name, MiddlewareInterface $middleware): self {
        $this->middlewares[$name] = $middleware;
        return $this;
    }

    /**
     * Unregister an available middleware.
     *
     * @param string $name
     * @return $this
     */
    public function unregisterMiddleware(string $name): self {
        unset($this->middlewares[$name]);
        return $this;
    }

    /**
     * Returns whether or not a middleware is registered.
     *
     * @param string $name
     * @return bool
     */
    public function isMiddlewareRegistered(string $name): bool {
        return isset($this->middlewares[$name]);
    }

    /**
     * Hyrdate a data specification.
     *
     * @param array $spec The specification that defines the data.
     * @param array $params Additional contextual data.
     * @return mixed Returns the hydrated data.
     */
    public function hydrate(array $spec, array $params = []) {
        $params[self::KEY_ROOT] = $spec;
        $result = $this->hydrateInternal($spec, $params);
        return $result;
    }

    /**
     * Internal implementation of the hydration.
     *
     * @param array $data The data to hydrate.
     * @param array $params Additional contextual data.
     * @return mixed Returns the mixed data.
     */
    private function hydrateInternal(array $data, array $params) {
        try {
            $result = [];
            // First resolve as much static data as possible in case there are exceptions.
            $recurse = [];
            foreach ($data as $key => $value) {
                if (in_array($key, [self::KEY_TYPE, self::KEY_MIDDLEWARE], true) || !is_array($value)) {
                    $result[$key] = $value;
                } else {
                    $result[$key] = null; // placeholder to maintain order
                    $recurse[$key] = $value;
                }
            }
            // Resolve any recursive items.
            foreach ($recurse as $key => $value) {
                $result[$key] = $this->hydrateInternal($value, $params);
            }

            // Look for middleware.
            if (array_key_exists(self::KEY_MIDDLEWARE, $result)) {
                $resolver = $this->makeMiddlewareResolver($result[self::KEY_MIDDLEWARE]);
            } else {
                $resolver = $this;
            }

            $result = $resolver->resolve($result, $params);
        } catch (\Exception $ex) {
            $result = $this->exceptionHandler->handleException($ex, $result, $params);
        }

        return $result;
    }

    /**
     * Resolve the data at a node.
     *
     * @param array $data
     * @param array $params
     * @return array|mixed
     * @throws ResolverNotFoundException Throws an exception when there isn't a resolver registered.
     */
    public function resolve(array $data, array $params) {
        if (isset($data[self::KEY_TYPE])) {
            $type = $data[self::KEY_TYPE];
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
     * Take an array of middleware and create a resolver for the chain.
     *
     * The middleware chain is an array in the form:
     *
     * ```json
     * [
     *     {"$type": "middlewareName", "param1": "value1", "param2": "value2"},
     *     {"$type": "middlewareName", "param1": "value1", "param2": "value2"}
     * ]
     * ```
     *
     * @param array $middlewares The middleware spec array.
     * @return DataResolverInterface
     * @throws MiddlewareNotFoundException Throws an exception if one of the middlewares is not found.
     */
    private function makeMiddlewareResolver(array $middlewares): DataResolverInterface {
        $result = $this;

        while (!empty($middlewares)) {
            $params = array_pop($middlewares);

            if (!is_array($params)) {
                throw new \TypeError('Each middleware must be an array.', 500);
            }

            $middleware = $this->getMiddleware($params[self::KEY_TYPE]);
            $result = new MiddlewareWrapper($middleware, $result, $params);
        }

        return $result;
    }

    /**
     * Get a middleware from the registered middlewares.
     *
     * @param string $name
     * @return MiddlewareInterface
     * @throws MiddlewareNotFoundException Throws an exception if the middleware isn't registered.
     */
    private function getMiddleware(string $name): MiddlewareInterface {
        if (!$this->isMiddlewareRegistered($name)) {
            throw new MiddlewareNotFoundException("Middleware not found: $name");
        }
        return $this->middlewares[$name];
    }

    /**
     * Get a resolver from the registered resolvers.
     *
     * @param string $type
     * @return DataResolverInterface
     * @throws ResolverNotFoundException Throws an exception if the resolver isn't registered.
     */
    private function getResolver(string $type): DataResolverInterface {
        if (!$this->isResolverRegistered($type)) {
            throw new ResolverNotFoundException("Resolver not registered: $type");
        }
        $resolver = $this->resolvers[$type];
        return $resolver;
    }
}
