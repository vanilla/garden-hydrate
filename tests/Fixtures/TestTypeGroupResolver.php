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

    /** @var string|null */
    private $hydrateGroup;

    /**
     * TestStringResolver constructor.
     *
     * @param string $type
     * @param string|null $hydrateGroup
     */
    public function __construct(string $type, string $hydrateGroup = null) {
        $this->type = $type;
        $this->hydrateGroup = $hydrateGroup;
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
     * @return string
     */
    public function getHydrateGroup(): string {
        if ($this->hydrateGroup === null) {
            return parent::getHydrateGroup();
        } else {
            return $this->hydrateGroup;
        }
    }
}
