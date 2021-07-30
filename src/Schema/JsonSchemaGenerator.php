<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Schema;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\ExceptionHandlerInterface;
use Garden\Hydrate\MiddlewareInterface;
use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Hydrate\Resolvers\LiteralResolver;
use Garden\Schema\Schema;

/**
 * Class for generating a schema out of a group of resolvers.
 */
class JsonSchemaGenerator {

    public const SCHEMA_DRAFT_7_URL = "http://json-schema.org/draft-07/schema";

    /** @var string The definition key used for the combined resolver types. */
    public const DEF_KEY_RESOLVER = 'resolver';

    /** @var string[] A reference to all resolver types. */
    private const REF_RESOLVER = [
        '$ref' => '#/$defs/' . self::DEF_KEY_RESOLVER
    ];

    /** @var string[] All built-in schema types in JSON schema. */
    public const ALL_SCHEMA_TYPES = [
        'array',
        'object',
        'integer',
        'string',
        'number',
        'boolean',
        'timestamp',
        'datetime',
        'null',
    ];

    /** @var AbstractDataResolver[] */
    private $resolvers;

    /** @var MiddlewareInterface[] */
    private $middlewares;

    /**
     * An array of all the resolver types.
     * Why these and not just array_keys($referencesByType)?
     * We need to know all possible types in order to generate any reference.
     * @var string[]
     */
    private $allTypes = [];

    /**
     * Store all the JSON schema references by their resolver type.
     * @var array[]
     */
    private $referencesByType = [];

    /**
     * Keep a mapping of all resolver groups to the types in them.
     *
     * @var array[]
     */
    private $typesByGroup = [
        'resolver' => [],
    ];

    /**
     * Constructor.
     *
     * @param AbstractDataResolver[] $resolvers
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(array $resolvers, array $middlewares) {
        $this->resolvers = $resolvers;

        // We need to know all the types before we build our references.
        $this->allTypes = array_map(function (AbstractDataResolver $resolver) {
            return $resolver->getType();
        }, $resolvers);

        // Now we can build the references.
        foreach ($this->resolvers as $resolver) {
            $this->applyResolverAsReference($resolver);
        }

        $this->middlewares = $middlewares;
    }

    /**
     * Get a schema with all resolver definitions and with allowing any structure of resolvers.
     *
     * @return Schema
     */
    public function getDefaultSchema(): Schema {
        $schema = new Schema(self::REF_RESOLVER);
        $schema->setField('$schema', self::SCHEMA_DRAFT_7_URL);
        $schema->setField('$defs', $this->createCombinedDefsArray());
        return $schema;
    }

    /**
     * Create an array of all resolver definitions to be used as a references.
     *
     * @return array
     */
    private function createCombinedDefsArray(): array {
        $defs = [];
        // Make sure we have defs for groups of things (included the root group that contains everything).
        foreach ($this->typesByGroup as $group => $types) {
            $defs[$group] = [
                'oneOf' => array_map([JsonSchemaGenerator::class, 'getDefReference'], $types),
            ];
        }

        // Add the individual items as refs.
        foreach ($this->referencesByType as $ref => $type) {
            $defs[$ref] = $type;
        }
        return $defs;
    }

    /**
     * Given the key of a definition, get an array referencing it.
     *
     * @param string $defKey The key of the definition. For example 'literal'.
     *
     * @return array The reference.
     */
    private static function getDefReference(string $defKey): array {
        return [
            '$ref' => '#/$defs/' . $defKey,
        ];
    }

    private function addHydrateToSchema() {}

    private function applyResolverAsReference(AbstractDataResolver $resolver) {
        $type = $resolver->getType();
        $schema = $resolver->getSchema() ?? self::makeNullSchemaArray($type);
        $schema->setField(['properties', DataHydrator::KEY_HYDRATE], [
            'type' => 'string',
            'enum' => [$type],
        ]);
        // Make sure hydrate key is required.
        $schema->setField(
            'required',
            array_unique(array_merge(
                $schema->getField('required', []),
                [DataHydrator::KEY_HYDRATE]
            ))
        );

        $group = $resolver->getResolverGroup();
        $this->referencesByType[$type] = $this->allowHydrateInSchema(
            $schema->getSchemaArray()
        );
        $this->pushTypeAndGroup($type, $group);
        $this->pushTypeAndGroup($type, self::DEF_KEY_RESOLVER);
    }

    private function allowHydrateInSchema(array $schemaArray): array {
        if (isset($schemaArray['properties'][DataHydrator::KEY_HYDRATE])) {
            // This is a hydrate spec.
            // We allow hydrate on everything but the hydrate key.
            $newProperties = [
//                DataHydrator::KEY_HYDRATE => $schemaArray['properties'][DataHydrator::KEY_HYDRATE],
            ];
            foreach ($schemaArray['properties'] as $key => $property) {
                if ($key === DataHydrator::KEY_HYDRATE) {
                    $newProperties[$key] = $property;
                } else {
                    $newProperties[$key] = $this->allowHydrateInSchema($property);
                }
            }
            $schemaArray['properties'] = $newProperties;
        } elseif (isset($schemaArray['oneOf'])) {
            // We already have a oneOf.
            // Modify the existing items
            $items = array_map([$this, 'oneOfOrResolve'], $schemaArray['oneOf']);
            $items[] = self::REF_RESOLVER;

            // Push into it.
            $schemaArray['oneOf'] = $items;
        } elseif (isset($schemaArray['anyOf'])) {
            // Modify the existing items
            $items = array_map([$this, 'oneOfOrResolve'], $schemaArray['anyOf']);

            // Wrap ourselves so we are { oneOf: [ {anyOf: }, REF_RESOLVER ] }
            $oneOf = $this->oneOfOrResolve([
                'anyOf' => $items
            ]);
            unset($schemaArray['anyOf']);
            $schemaArray = array_merge_recursive($schemaArray, $oneOf);
        } elseif (isset($schemaArray['properties'])) {
            $modified = [];
            foreach ($schemaArray['properties'] as $property => $definition) {
                $modified[$property] = $this->allowHydrateInSchema($definition);
            }
            $schemaArray = $this->oneOfOrResolve($modified);
        } else {
            $schemaArray = $this->oneOfOrResolve($schemaArray);
        }
        $schemaArray = $this->patchOneOf($schemaArray);
        return $schemaArray;
    }

    /**
     * Ensure that we have a clear discriminator property to go with a hydrate oneOf.
     *
     * Essentially most language services will not go find properties to autocomplete until you've chosen your discriminator.
     *
     * @see https://github.com/microsoft/vscode-json-languageservice/issues/86#issuecomment-820984129
     *
     * @param array $schemaArray
     * @return array
     */
    private function patchOneOf(array $schemaArray): array {
        if (isset($schemaArray['oneOf'])) {
            $hydrateAll = false;
            foreach ($schemaArray['oneOf'] as $of) {
                $ofRef = $of['$ref'] ?? null;
                if ($ofRef === self::REF_RESOLVER['$ref']) {
                    $hydrateAll = true;
                    break;
                }
            }
            if ($hydrateAll) {
                // Currently this CANNOT be a reference.
                // Most autocompleting tools require this level of verbosity to not fall apart.
                $schemaArray['properties'] = [
                    DataHydrator::KEY_HYDRATE => [
                        'type' => 'string',
                        'enum' => $this->allTypes,
                    ]
                ];
                // Make sure it's required
                $schemaArray['required'] = array_unique(array_merge(
                    $allHydrateValues['required'] ?? [],
                    [DataHydrator::KEY_HYDRATE]
                ));
            }
        }
        return $schemaArray;
    }

    private function oneOfOrResolve(array $typeArray): array {
        return [
            'oneOf' => [
                $typeArray,
                self::REF_RESOLVER,
            ]
        ];
    }

    /**
     * Track references between a type and group.
     *
     * @param string $type
     * @param string $group
     */
    private function pushTypeAndGroup(string $type, string $group) {
        $this->typesByGroup[$group] = array_unique(array_merge(
            $this->typesByGroup[$group] ?? [],
            [$type]
        ));
    }

    private function makeNullSchemaArray(string $type): Schema {
        return Schema::parse([
            'type' => 'object',
            'allowAdditionalProperties' => true,
            'required' => [DataHydrator::KEY_HYDRATE],
        ]);
    }
}
