<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Schema;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Exception\InvalidHydrateSpecException;
use Garden\Schema\Schema;

/**
 * A schema that allows any subfields to be recursively hydrated.
 */
class HydrateableSchema extends Schema {

    public const X_NO_HYDRATE = 'x-no-hydrate';
    public const X_FORCE_HYDRATE_ITEMS = 'x-force-hydrate-items';
    public const X_HYDRATE_GROUP = 'x-hydrate-group';

    /** @var string[] All built-in schema types in JSON schema. */
    public const ALL_SCHEMA_TYPES = [
        'array',
        'object',
        'string',
        'number',
        'boolean',
        'null',
    ];

    /** @var string[] All built-in schema types in JSON schema. */
    private const NON_OBJECT_SCHEMA_TYPES = [
        'array',
        'string',
        'number',
        'boolean',
        'null',
    ];


    public const ANY_OBJECT_SCHEMA_ARRAY = [
        'type' => 'object',
        'additionalProperties' => true,
    ];

    /**
     * Constructor
     *
     * @param array $schemaArray The schema array to use.
     * @param ?string $ownHydrateType The key of our own data resolver.
     *
     * @throws InvalidHydrateSpecException If the root type does not allow an object.
     */
    public function __construct(array $schemaArray, ?string $ownHydrateType = null) {

        if ($this->hasNonObjectSchemaType($schemaArray)) {
            $message = 'Hydrateable schema\'s root type must allow an object.'
                .'If you need to support a non-object root type use the literal resolver.';
            throw new InvalidHydrateSpecException($message);
        }

        if ($ownHydrateType !== null) {
            // Make sure we are an object.
            $schemaArray['type'] = 'object';

            // Make sure hydrate key is required.
            $schemaArray['properties'] = $schemaArray['properties'] ?? [];
            $schemaArray['properties'][DataHydrator::KEY_HYDRATE] = [
                'type' => 'string',
                'enum' => [$ownHydrateType],
            ];
            $this->markHydrateRequired($schemaArray);
        }

        $schemaArray = $this->allowHydrateInSchema($schemaArray, $ownHydrateType !== null);
        parent::__construct($schemaArray);
    }

    /**
     * Parse a short schema and return the associated schema.
     *
     * @inheritdoc
     * @psalm-suppress LessSpecificReturnStatement
     */
    public static function parse(array $arr, ...$args) {
        // We can't use parse directly because $schema is private and we can't modify it in a subclass after being instantiated.
        $parsed = Schema::parse($arr);
        $hydrateable = new HydrateableSchema($parsed->getSchemaArray(), ...$args);
        return $hydrateable;
    }

    /**
     * Allow hydrate in a schema.
     *
     * @param array $schemaArray
     * @param bool $isTopLevel Set to true to indicate that we are the top level definition of the schema and not a property.
     *
     * @return array
     */
    private function allowHydrateInSchema(array $schemaArray, bool $isTopLevel = false): array {
        $notHydrateable = $isTopLevel || ($schemaArray[self::X_NO_HYDRATE] ?? false);

        // Do items first.
        if (isset($schemaArray['items'])) {
            $schemaArray['items'] = $this->allowHydrateInSchema($schemaArray['items']);
        }

        if (isset($schemaArray['type']) && $schemaArray['type'] === 'object' && !isset($schemaArray['properties'])) {
            // Properties weren't described so we need some discriminator.
            $this->markNotHydrate($schemaArray);
        }

        if (isset($schemaArray['oneOf'])) {
            // Push into the existing items.
            $schemaArray['oneOf'][] = JsonSchemaGenerator::getDefReference(JsonSchemaGenerator::ROOT_HYDRATE_GROUP);
        } elseif (isset($schemaArray['anyOf'])) {
            // Modify the existing items
            $items = array_map([$this, 'oneOfWithHydrate'], $schemaArray['anyOf']);

            // Wrap ourselves so we are { oneOf: [ {anyOf: }, REF_RESOLVER ] }
            $oneOf = $this->oneOfWithHydrate([
                'anyOf' => $items
            ]);
            unset($schemaArray['anyOf']);
            $schemaArray = array_merge_recursive($schemaArray, $oneOf);
        } elseif (isset($schemaArray['properties'])) {
            // This is a hydrate spec.
            // We allow hydrate on everything but the hydrate key.
            $newProperties = [];
            $hasHydrate = false;
            foreach (($schemaArray['properties'] ?? []) as $key => $property) {
                if ($property[self::X_NO_HYDRATE] ?? false) {
                    if (($property[self::X_FORCE_HYDRATE_ITEMS] ?? false) && isset($property['items'])) {
                        $property['items'] = $this->allowHydrateInSchema($property['items']);
                    }
                    // No hydrate allowed here.
                    $newProperties[$key] = $property;
                    continue;
                }
                if ($key === DataHydrator::KEY_HYDRATE) {
                    $hasHydrate = true;
                    $newProperties[$key] = $property;
                } else {
                    if ($property instanceof Schema) {
                        $property = $property->getSchemaArray();
                    }
                    $newProperties[$key] = $this->allowHydrateInSchema($property);
                }
            }
            $schemaArray['properties'] = $newProperties;

            // If we are a nested property (or a non-object primitive type)
            // we will become a oneOf type, creating a union with hydrate.
            if (!$hasHydrate && !$notHydrateable) {
                $this->markNotHydrate($schemaArray);
                $schemaArray = $this->oneOfWithHydrate($schemaArray);
            }
        } elseif (!$notHydrateable) {
            // oneOfWithHydrate is required if there is a different possible object type here (then $literal is needed).
            // Normally by this point we've already ruled out object types and wouldn't need this, unless the item is a ref.
            // If it's a ref, it could be anything.
            $schemaArray = $this->oneOfWithHydrate($schemaArray);
        }
        return $schemaArray;
    }

    /**
     * Add a not of hydrate key.
     *
     * @param array $schemaArray
     */
    private function markNotHydrate(array &$schemaArray) {
        $schemaArray['not'] = [
            "required" => [DataHydrator::KEY_HYDRATE],
        ];
    }

    /**
     * Take a schema array and union it with the the root hydrator.
     *
     * @param array $schemaArray
     * @return array[]
     */
    private function oneOfWithHydrate(array $schemaArray): array {
        // Clear the description, we're hoisting it.
        $hydrateGroup = $schemaArray[self::X_HYDRATE_GROUP] ?? JsonSchemaGenerator::ROOT_HYDRATE_GROUP;
        $description = $schemaArray['description'] ?? null;
        unset($schemaArray['description']);
        $schemaArray = [
            'oneOf' => [
                $schemaArray,
                JsonSchemaGenerator::getDefReference($hydrateGroup),
            ]
        ];
        // Hoist up the description if we had one.
        if ($description !== null) {
            $schemaArray['description'] = $description;
        }

        return $schemaArray;
    }

    /**
     * Check if a schema array has a non-object type.
     *
     * @param array $schemaArray
     *
     * @return bool
     */
    private function hasNonObjectSchemaType(array $schemaArray): bool {
        if (!isset($schemaArray['type'])) {
            return false;
        }

        // Make sure we are always an array of types.
        $type = $schemaArray['type'];
        $typeInArray = is_array($type) ? $type : [$type];

        $intersected = array_intersect($typeInArray, self::NON_OBJECT_SCHEMA_TYPES);
        $hasNonObjectType = count($intersected) > 0;
        return $hasNonObjectType;
    }

    /**
     * Push into or create a 'required' property on the given array.
     *
     * @param array $someArray The array to mark a required property on.
     */
    private function markHydrateRequired(array &$someArray) {
        $someArray['required'] = array_unique(array_merge(
            $someArray['required'] ?? [],
            [DataHydrator::KEY_HYDRATE]
        ));
    }
}
