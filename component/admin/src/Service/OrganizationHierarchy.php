<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

final class OrganizationHierarchy
{
    /**
     * Returns all descendant ids for a record in the supplied organization set.
     *
     * @param array<int, object> $items
     * @return array<int, int>
     */
    public static function descendantIds(array $items, int $id): array
    {
        if ($id < 1 || $items === []) {
            return [];
        }

        $childrenByParent = [];
        foreach ($items as $item) {
            $parentId = (int) ($item->parent_id ?? 0);
            $childId = (int) ($item->id ?? 0);

            if ($childId > 0) {
                $childrenByParent[$parentId][] = $childId;
            }
        }

        $seen = [$id => true];
        $frontier = [$id];
        $descendants = [];

        for ($depth = 0; $depth < 100 && $frontier !== []; $depth++) {
            $next = [];

            foreach ($frontier as $parentId) {
                foreach ($childrenByParent[$parentId] ?? [] as $childId) {
                    if (isset($seen[$childId])) {
                        continue;
                    }

                    $seen[$childId] = true;
                    $descendants[] = $childId;
                    $next[] = $childId;
                }
            }

            $frontier = $next;
        }

        return $descendants;
    }

    /**
     * Returns the hierarchy path from the root organization to the selected
     * organization. Corrupt cycles are stopped safely.
     *
     * @param array<int, object> $items
     * @return array<int, object>
     */
    public static function path(array $items, int $id): array
    {
        if ($id < 1 || $items === []) {
            return [];
        }

        $byId = [];
        foreach ($items as $item) {
            $itemId = (int) ($item->id ?? 0);
            if ($itemId > 0) {
                $byId[$itemId] = $item;
            }
        }

        if (!isset($byId[$id])) {
            return [];
        }

        $path = [];
        $seen = [];
        $cursor = $id;

        for ($depth = 0; $depth < 100 && $cursor > 0; $depth++) {
            if (isset($seen[$cursor]) || !isset($byId[$cursor])) {
                break;
            }

            $seen[$cursor] = true;
            array_unshift($path, $byId[$cursor]);
            $cursor = (int) ($byId[$cursor]->parent_id ?? 0);
        }

        return $path;
    }

    /**
     * Returns all descendant organization records in tree order. The
     * hierarchy_depth property is relative to the selected organization, so
     * direct children have depth 0.
     *
     * @param array<int, object> $items
     * @return array<int, object>
     */
    public static function descendants(array $items, int $id): array
    {
        if ($id < 1 || $items === []) {
            return [];
        }

        $childrenByParent = [];
        foreach ($items as $item) {
            $parentId = (int) ($item->parent_id ?? 0);
            $itemId = (int) ($item->id ?? 0);

            if ($itemId > 0) {
                $childrenByParent[$parentId][] = $item;
            }
        }

        $sortByName = static function (object $left, object $right): int {
            $nameCompare = strnatcasecmp((string) ($left->name ?? ''), (string) ($right->name ?? ''));

            return $nameCompare !== 0 ? $nameCompare : ((int) ($left->id ?? 0) <=> (int) ($right->id ?? 0));
        };

        foreach ($childrenByParent as &$children) {
            usort($children, $sortByName);
        }
        unset($children);

        $ordered = [];
        $seen = [$id => true];
        $append = function (int $parentId, int $depth) use (&$append, &$ordered, &$seen, $childrenByParent): void {
            foreach ($childrenByParent[$parentId] ?? [] as $child) {
                $childId = (int) ($child->id ?? 0);

                if ($childId < 1 || isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $child->hierarchy_depth = min(max($depth, 0), 100);
                $ordered[] = $child;
                $append($childId, $depth + 1);
            }
        };

        $append($id, 0);

        return $ordered;
    }

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
