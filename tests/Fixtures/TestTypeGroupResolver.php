<?php
/**
 * @author Adam Charron <adam@charrondev.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Tests\Fixtures;

use Garden\Hydrate\Resolvers\AbstractDataResolver;

/**
 * A test resolver that adds a string to the data.
 */
class TestTypeGroupResolver extends AbstractDataResolver {
    /**
     * @var string
     */
    private $type;

    /** @var string[]|null */
    private $hydrateGroups;

    /**
     * TestStringResolver constructor.
     *
     * @param string $type
     * @param string[]|null $hydrateGroups
     */
    public function __construct(string $type, array $hydrateGroups = null) {
        $this->type = $type;
        $this->hydrateGroups = $hydrateGroups;
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveInternal(array $data, array $params = []) {
        return 'testTypeGroup';
    }

    /**
     * @inheritDoc
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getHydrateGroups(): array {
        if ($this->hydrateGroups === null) {
            return parent::getHydrateGroups();
        } else {
            return $this->hydrateGroups;
        }
    }
}
