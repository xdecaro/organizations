<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

final class OrganizationHierarchy
{
    /**
     * Orders a filtered organization result set as a tree and annotates each
     * object with hierarchy_depth. Parents that are not part of the filtered
     * result are treated as roots so searches and filters remain readable.
     *
     * @param array<int, object> $items
     * @return array<int, object>
     */
    public static function order(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $byId = [];
        $childrenByParent = [];

        foreach ($items as $item) {
            $id = (int) $item->id;
            $parentId = (int) $item->parent_id;
            $byId[$id] = $item;
            $childrenByParent[$parentId][] = $item;
        }

        $sortByName = static function (object $left, object $right): int {
            $nameCompare = strnatcasecmp((string) $left->name, (string) $right->name);

            return $nameCompare !== 0 ? $nameCompare : ((int) $left->id <=> (int) $right->id);
        };

        foreach ($childrenByParent as &$children) {
            usort($children, $sortByName);
        }
        unset($children);

        $roots = [];
        foreach ($items as $item) {
            $parentId = (int) $item->parent_id;
            if ($parentId === 0 || !isset($byId[$parentId])) {
                $roots[] = $item;
            }
        }
        usort($roots, $sortByName);

        $ordered = [];
        $seen = [];
        $append = function (object $item, int $depth) use (&$append, &$ordered, &$seen, $childrenByParent): void {
            $id = (int) $item->id;
            if (isset($seen[$id])) {
                return;
            }

            $seen[$id] = true;
            $item->hierarchy_depth = min(max($depth, 0), 100);
            $ordered[] = $item;

            foreach ($childrenByParent[$id] ?? [] as $child) {
                $append($child, $depth + 1);
            }
        };

        foreach ($roots as $root) {
            $append($root, 0);
        }

        // Cyclic/corrupt legacy data must not make records disappear.
        $remaining = $items;
        usort($remaining, $sortByName);
        foreach ($remaining as $item) {
            if (!isset($seen[(int) $item->id])) {
                $append($item, 0);
            }
        }

        return $ordered;
    }
}
