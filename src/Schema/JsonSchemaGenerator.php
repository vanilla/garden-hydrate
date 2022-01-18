<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Schema;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Schema\Schema;

/**
 * Class for generating a schema out of a group of resolvers.
 */
class JsonSchemaGenerator {

    public const SCHEMA_DRAFT_7_URL = "http://json-schema.org/draft-07/schema";

    /** @var string The definition key used for the combined resolver types. */
    public const ROOT_HYDRATE_GROUP = 'resolver';

    /** @var AbstractDataResolver[] */
    private $resolvers;

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
        self::ROOT_HYDRATE_GROUP => [],
    ];

    /* @var DataHydrator */
    private $dataHydrator;

    /**
     * Constructor.
     *
     * @param AbstractDataResolver[] $resolvers
     * @param DataHydrator $dataHydrator
     */
    public function __construct(array $resolvers, DataHydrator $dataHydrator) {
        $this->resolvers = $resolvers;
        $this->dataHydrator = $dataHydrator;

        // We need to know all the types before we build our references.
        foreach ($this->resolvers as $resolver) {
            $type = $resolver->getType();
            $groups = $resolver->getHydrateGroups();
            foreach ($groups as $group) {
                $this->pushTypeAndGroup($type, $group);
            }
            $this->pushTypeAndGroup($type, self::ROOT_HYDRATE_GROUP);
        }
        $this->allTypes = array_map(function (AbstractDataResolver $resolver) {
            return $resolver->getType();
        }, array_values($resolvers));

        // Now we can build the references.
        foreach ($this->resolvers as $resolver) {
            $this->applyResolverAsReference($resolver);
        }
    }

    /**
     * @return array[]
     */
    public function getTypesByGroup(): array {
        return $this->typesByGroup;
    }

    /**
     * Get a schema with all resolver definitions and with allowing any structure of resolvers.
     *
     * @return Schema
     */
    public function getDefaultSchema(): Schema {
        $schema = new Schema(self::getDefReference());
        $schema = $this->decorateSchema($schema);
        return $schema;
    }

    /**
     * Decorate an existing schema with the definitions of the hydration.
     *
     * @param Schema $schema The schema to decorate.
     *
     * @return Schema
     */
    public function decorateSchema(Schema $schema): Schema {
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

        $middlewareSchema = new Schema([
            'description' => 'Apply middlewares over the node',
            'type' => 'object',
            'properties' => []
        ]);

        foreach ($this->dataHydrator->getMiddlewares() as $middleware) {
            $middlewareSchema->setField('properties.' . $middleware->getType(), $middleware->getSchema());
        }

        // Make sure we have defs for groups of things (included the root group that contains everything).
        foreach ($this->typesByGroup as $group => $types) {
            $defs[$group] = [
                'oneOf' => array_map([JsonSchemaGenerator::class, 'getDefReference'], $types),
                'properties' => [
                    DataHydrator::KEY_HYDRATE => [
                        'type' => 'string',
                        'enum' => $types,
                    ],
                    DataHydrator::KEY_MIDDLEWARE => $middlewareSchema,
                ],
                'required' => [DataHydrator::KEY_HYDRATE]
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
    public static function getDefReference(string $defKey = self::ROOT_HYDRATE_GROUP): array {
        return [
            '$ref' => '#/$defs/' . $defKey,
        ];
    }

    /**
     * Take a resolver and create a hydrateable schema from it.
     * Load all/any active middleware schema
     * Store the mappings of the type, group, and schema.
     *
     * @param AbstractDataResolver $resolver
     */
    private function applyResolverAsReference(AbstractDataResolver $resolver) {
        $type = $resolver->getType();
        $schema = $resolver->getSchema();
        $schemaArray = $schema ? $schema->getSchemaArray() : HydrateableSchema::ANY_OBJECT_SCHEMA_ARRAY;
        $hydrateableSchema = new HydrateableSchema($schemaArray, $type);
        $this->referencesByType[$type] = $hydrateableSchema->getSchemaArray();
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
}
