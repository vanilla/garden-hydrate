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

    /**
     * ReflectedFunctionResolver constructor.
     *
     * @param callable $function The function that will resolve the data.
     */
    public function __construct(callable $function) {
        $this->function = $function;
        [$this->schema, $this->paramNames, $this->variadic] = $this->reflectSchema($function);
    }

    /**
     * Reflect a callable to get its parameters and parameter schema.
     *
     * @param callable $callable
     * @return array Returns an array in the form `[$schema, $paramNames]`.
     * @psalm-suppress PossiblyNullReference
     */
    private function reflectSchema(callable $callable): array {
        $func = $this->createReflectionFunction($callable);
        $paramNames = [];
        $variadic = '';
        $properties = [];
        $required = [];

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

            $properties[$param->getName()] = $schema;
        }

        $schema = Schema::parse([
            'type' => 'object',
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
        } else {
            $result = new ReflectionFunction($callable);
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
}
