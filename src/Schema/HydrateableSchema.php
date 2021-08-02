<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Schema;

use Garden\Hydrate\DataHydrator;
use Garden\Schema\Schema;

/**
 * A schema that allows any subfields to be recursively hydrated.
 */
class HydrateableSchema extends Schema {

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

    public const ANY_OBJECT_SCHEMA_ARRAY = [
        'type' => 'object',
        'allowAdditionalProperties' => true,
    ];

    /** @var string[] */
    private $hydrateTypes;

    /** @var string */
    private $ownHydrateType;

    /**
     * Constructor
     *
     * @param array $schema The schema array to use.
     * @param string $ownHydrateType The key of our own data resolver.
     * @param array $hydrateTypes An array of all hydrate types. This is needed for recursive property typing.
     */
    public function __construct(array $schema, string $ownHydrateType, array $hydrateTypes = []) {
        $this->hydrateTypes = $hydrateTypes;
        $this->ownHydrateType = $ownHydrateType;
        // Make sure hydrate key is required.
        $schema['properties'] = $schema['properties'] ?? [];
        $schema['properties'][DataHydrator::KEY_HYDRATE] = [
            'type' => 'string',
            'enum' => [$this->ownHydrateType],
        ];
        $schema = $this->allowHydrateInSchema($schema);
        $this->markHydrateRequired($schema);
        parent::__construct($schema);
    }

    /**
     * Allow hydrate in a schema.
     *
     * @param array $schemaArray
     *
     * @return array
     */
    private function allowHydrateInSchema(array $schemaArray): array {
        if (isset($schemaArray['properties'])) {
            // This is a hydrate spec.
            // We allow hydrate on everything but the hydrate key.
            $newProperties = [];
            $hasHydrate = false;
            foreach ($schemaArray['properties'] as $key => $property) {
                if ($key === DataHydrator::KEY_HYDRATE) {
                    $hasHydrate = true;
                    $newProperties[$key] = $property;
                } else {
                    $newProperties[$key] = $this->allowHydrateInSchema($property);
                }
            }
            $schemaArray['properties'] = $newProperties;
            if (!$hasHydrate) {
                $schemaArray = $this->oneOfWithHydrate($schemaArray);
            }
        } elseif (isset($schemaArray['oneOf'])) {
            // We already have a oneOf.
            // Modify the existing items
            $items = $schemaArray['oneOf'];
            $items[] = JsonSchemaGenerator::REF_RESOLVER;

            // Push into it.
            $schemaArray['oneOf'] = $items;
        } elseif (isset($schemaArray['anyOf'])) {
            // Modify the existing items
            $items = array_map([$this, 'oneOfWithHydrate'], $schemaArray['anyOf']);

            // Wrap ourselves so we are { oneOf: [ {anyOf: }, REF_RESOLVER ] }
            $oneOf = $this->oneOfWithHydrate([
                'anyOf' => $items
            ]);
            unset($schemaArray['anyOf']);
            $schemaArray = array_merge_recursive($schemaArray, $oneOf);
        } else {
            // addHydrateDiscriminator is required if there is a different possible object type here (then $literal is needed).
            // Normally by this point we've already ruled out object types and wouldn't need this, unless the item is a ref.
            // If it's a ref, it could be anything.
            $schemaArray = $this->oneOfWithHydrate($schemaArray);
        }
        return $schemaArray;
    }

    /**
     * Take a schema array an union it with the
     *
     * @param array $schemaArray
     * @return array[]
     */
    private function oneOfWithHydrate(array $schemaArray): array {
        // Clear the description, we're hoisting it.
        $description = $schemaArray['description'] ?? null;
        unset($schemaArray['description']);
        $schemaArray = [
            'oneOf' => [
                $schemaArray,
                JsonSchemaGenerator::REF_RESOLVER,
            ]
        ];
        // Put back the description if there was one.
        if ($description !== null) {
            $schemaArray['description'] = $description;
        }

        // Add a discriminator field for autocomplete.
        foreach ($schemaArray['oneOf'] as $of) {
            $ofRef = $of['$ref'] ?? null;
            if ($ofRef === JsonSchemaGenerator::REF_RESOLVER['$ref']) {
                break;
            }
        }
        // Currently this CANNOT be a reference.
        // Most autocompleting tools require this level of verbosity to not fall apart.
        $schemaArray['properties'] = [
            DataHydrator::KEY_HYDRATE => [
                'type' => 'string',
                'enum' => $this->hydrateTypes,
            ]
        ];
        $this->markHydrateRequired($schemaArray);

        return $schemaArray;
    }

    /**
     * Push into or create a 'required' property on the given array.
     *
     * @param array|Schema $someArray The array to mark a required property on.
     */
    private function markHydrateRequired(&$someArray) {
        $someArray['required'] = array_unique(array_merge(
            $someArray['required'] ?? [],
            [DataHydrator::KEY_HYDRATE]
        ));
    }
}
