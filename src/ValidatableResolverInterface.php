<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate;

/**
 * Represents a data resolver that can be validated.
 */
interface ValidatableResolverInterface extends DataResolverInterface {
    /**
     * Validate the data at the node.
     *
     * Resolvers are responsible for returning valid data or throwing a validation exception.
     *
     * @param array $data The data to validate.
     * @return array Returns the valid data.
     */
    public function validate(array $data): array;
}
