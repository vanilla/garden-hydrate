<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Resolvers;

use Garden\Schema\Schema;
use ReflectionFunction;
use ReflectionMethod;

/**
 * A data resolver that will calls a function with named parameters.
 */
class FunctionResolver extends AbstractDataResolver {

    public const TYPE = "function";

    /**
     * @var callable The function that is invoked for the resolver.
     */
    private $function;

    /**
     * @var array
     */
    private $paramNames = [];

    /**
     * @var string
     */
    private $variadic = '';

    /** @var string */
    private $resolverType;

    /**
     * ReflectedFunctionResolver constructor.
     *
     * @param callable $function The function that will resolve the data.
     * @param string|null $resolverType A name to use as the resolver type. If left empty one will be generated.
     */
    public function __construct(callable $function, string $resolverType = null) {
        $this->function = $function;
        $func = $this->createReflectionFunction($function);
        $this->resolverType = $resolverType ?? $func->getName();
        [$this->schema, $this->paramNames, $this->variadic] = $this->reflectSchema($func);
    }

    /**
     * Reflect a callable to get its parameters and parameter schema.
     *
     * @param \ReflectionFunctionAbstract $func
     * @return array Returns an array in the form `[$schema, $paramNames]`.
     * @psalm-suppress PossiblyNullReference
     */
    private function reflectSchema(\ReflectionFunctionAbstract $func): array {
        $paramNames = [];
        $variadic = '';
        $properties = [];
        $required = [];

        $funcName = $func->getName();
        if ($func instanceof ReflectionMethod) {
            $funcName = $func->getDeclaringClass()->getName() . '::' . $funcName;
        }
        foreach ($func->getParameters() as $param) {
            if ($param->isVariadic()) {
                $variadic = $param->getName();
            } else {
                $paramNames[] = $param->getName();

                if (!$param->isOptional()) {
                    $required[] = $param->getName();
                }
            }

            $schema = [];
            if ($param->isVariadic()) {
                $schema = ['type' => 'array', 'items' => []];
            } elseif (null !== $param->getType() && $param->getType()->isBuiltin()) {
                $typeName = $param->getType()->getName();
                switch ($typeName) {
                    case 'bool':
                        $schema['type'] = 'boolean';
                        break;
                    case 'int':
                        $schema['type'] = 'integer';
                        break;
                    case 'string':
                        $schema['type'] = $typeName;
                        break;
                    case 'float':
                        $schema['type'] = 'number';
                        break;
                    case 'array':
                        $schema['type'] = ['array', 'object'];
                        $schema['items'] = [];
                        break;
                }

                if ($param->allowsNull()) {
                    $schema['allowNull'] = true;
                }
                if ($param->isDefaultValueAvailable()) {
                    $schema['default'] = $param->getDefaultValue();
                }
            }

            // Fallback so we don't end up with an empty schema array.
            if (!isset($schema['type'])) {
                $schema['type'] = ['boolean', 'string', 'number', 'array', 'object'];
            }

            $properties[$param->getName()] = $schema;
        }
        $propertiesName = implode(", ", array_keys($properties));

        $schema = Schema::parse([
            'type' => 'object',
            'description' => "Call the function `$funcName($propertiesName)`",
            'properties' => $properties,
            'required' => $required,
        ]);

        return [$schema, $paramNames, $variadic];
    }

    /**
     * Create a reflection function from a callable.
     *
     * @param callable $callable
     * @return \ReflectionFunctionAbstract
     * @psalm-suppress InvalidArgument
     */
    private function createReflectionFunction(callable $callable): \ReflectionFunctionAbstract {
        if (is_array($callable)) {
            $result = new ReflectionMethod(...$callable);
        } elseif (is_string($callable) || $callable instanceof \Closure) {
            $result = new ReflectionFunction($callable);
        } else {
            $result = new ReflectionMethod($callable, '__call');
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveInternal(array $data, array $params) {
        $args = [];
        foreach ($this->paramNames as $paramName) {
            if (array_key_exists($paramName, $data)) {
                $args[] = $data[$paramName];
            } else {
                break; // @codeCoverageIgnore
            }
        }

        if (!empty($this->variadic) && !empty($data[$this->variadic])) {
            $args = array_merge($args, $data[$this->variadic]);
        }

        $result = call_user_func_array($this->function, $args);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return $this->resolverType;
    }
}
