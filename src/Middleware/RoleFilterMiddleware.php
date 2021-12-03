<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Hydrate\Middleware;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\MiddlewareInterface;
use Garden\JSON\Transformer;

/**
 * Middleware that transforms the data after it has been resolved.
 */
class RoleFilterMiddleware implements MiddlewareInterface {
    /**
     * {@inheritDoc}
     */
    public function process(array $data, array $params, DataResolverInterface $next) {
        if (!empty($data['layout'])) {
            $userRoleIDs = \Gdn::userModel()->getRoleIDs(\Gdn::authenticator()->getIdentity());
            $parsedLayout = $this->parseHydratorForRoleID($data['layout'], $userRoleIDs);
            $data['layout'] = $parsedLayout;
        }
        return $data;
    }

    private function parseHydratorForRoleID($layout, $roleIds) {
        $nodes = new \RecursiveArrayIterator($layout);
        $objects = new \RecursiveIteratorIterator($nodes);
        foreach ($objects as $sub) {
            $subObject = $objects->getSubIterator();
            $test = true;
            if ($objRoleIds = $subObject['$middleware']['role-filter']) {
                if (array_intersect($objRoleIds, $roleIds)) {
                    unset($subObject['$middleware']);
                    $result[] = iterator_to_array($subObject);
                }
            }
        }
        return $result;
    }
}
