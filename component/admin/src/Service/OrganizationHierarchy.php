<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

final class OrganizationHierarchy
{
    public static function descendantIds(array $items, int $id): array
    {
        if ($id < 1 || $items === []) { return []; }
        $childrenByParent = [];
        foreach ($items as $item) {
            $parentId = (int) ($item->parent_id ?? 0); $childId = (int) ($item->id ?? 0);
            if ($childId > 0) { $childrenByParent[$parentId][] = $childId; }
        }
        $seen = [$id => true]; $frontier = [$id]; $descendants = [];
        for ($depth = 0; $depth < 100 && $frontier !== []; $depth++) {
            $next = [];
            foreach ($frontier as $parentId) {
                foreach ($childrenByParent[$parentId] ?? [] as $childId) {
                    if (isset($seen[$childId])) { continue; }
                    $seen[$childId] = true; $descendants[] = $childId; $next[] = $childId;
                }
            }
            $frontier = $next;
        }
        return $descendants;
    }

    public static function path(array $items, int $id): array
    {
        if ($id < 1 || $items === []) { return []; }
        $byId = [];
        foreach ($items as $item) { $itemId = (int) ($item->id ?? 0); if ($itemId > 0) { $byId[$itemId] = $item; } }
        if (!isset($byId[$id])) { return []; }
        $path = []; $seen = []; $cursor = $id;
        for ($depth = 0; $depth < 100 && $cursor > 0; $depth++) {
            if (isset($seen[$cursor]) || !isset($byId[$cursor])) { break; }
            $seen[$cursor] = true; array_unshift($path, $byId[$cursor]); $cursor = (int) ($byId[$cursor]->parent_id ?? 0);
        }
        return $path;
    }

    public static function descendants(array $items, int $id): array
    {
        if ($id < 1 || $items === []) { return []; }
        $childrenByParent = [];
        foreach ($items as $item) { $parentId = (int) ($item->parent_id ?? 0); $itemId = (int) ($item->id ?? 0); if ($itemId > 0) { $childrenByParent[$parentId][] = $item; } }
        $sortByName = static fn(object $l, object $r): int => strnatcasecmp((string) ($l->name ?? ''), (string) ($r->name ?? '')) ?: ((int) ($l->id ?? 0) <=> (int) ($r->id ?? 0));
        foreach ($childrenByParent as &$children) { usort($children, $sortByName); } unset($children);
        $ordered = []; $seen = [$id => true];
        $append = function (int $parentId, int $depth) use (&$append, &$ordered, &$seen, $childrenByParent): void {
            foreach ($childrenByParent[$parentId] ?? [] as $child) {
                $childId = (int) ($child->id ?? 0); if ($childId < 1 || isset($seen[$childId])) { continue; }
                $seen[$childId] = true; $child->hierarchy_depth = min(max($depth, 0), 100); $ordered[] = $child; $append($childId, $depth + 1);
            }
        };
        $append($id, 0); return $ordered;
    }

    public static function order(array $items): array
    {
        if ($items === []) { return []; }
        $byId = []; $childrenByParent = [];
        foreach ($items as $item) { $id = (int) $item->id; $parentId = (int) $item->parent_id; $byId[$id] = $item; $childrenByParent[$parentId][] = $item; }
        $sortByName = static fn(object $l, object $r): int => strnatcasecmp((string) $l->name, (string) $r->name) ?: ((int) $l->id <=> (int) $r->id);
        foreach ($childrenByParent as &$children) { usort($children, $sortByName); } unset($children);
        $roots = [];
        foreach ($items as $item) { $parentId = (int) $item->parent_id; if ($parentId === 0 || !isset($byId[$parentId])) { $roots[] = $item; } }
        usort($roots, $sortByName);
        $ordered = []; $seen = [];
        $append = function (object $item, int $depth) use (&$append, &$ordered, &$seen, $childrenByParent): void {
            $id = (int) $item->id; if (isset($seen[$id])) { return; }
            $seen[$id] = true; $item->hierarchy_depth = min(max($depth, 0), 100); $ordered[] = $item;
            foreach ($childrenByParent[$id] ?? [] as $child) { $append($child, $depth + 1); }
        };
        foreach ($roots as $root) { $append($root, 0); }
        $remaining = $items; usort($remaining, $sortByName);
        foreach ($remaining as $item) { if (!isset($seen[(int) $item->id])) { $append($item, 0); } }
        return $ordered;
    }

    public static function analyze(array $items): array
    {
        $byId = [];
        foreach ($items as $item) { $id = (int) ($item->id ?? 0); if ($id > 0) { $byId[$id] = $item; } }
        $selfParent = []; $missingParent = []; $cycles = [];
        foreach ($byId as $id => $item) {
            $parent = (int) ($item->parent_id ?? 0);
            if ($parent === $id) { $selfParent[] = $id; }
            if ($parent > 0 && !isset($byId[$parent])) { $missingParent[] = $id; }
            $seen = []; $cursor = $id;
            for ($depth = 0; $depth < 100 && $cursor > 0; $depth++) {
                if (isset($seen[$cursor])) {
                    foreach (array_keys($seen) as $cycleId) { if ($cycleId === $cursor || isset($seen[$cursor])) { $cycles[$cycleId] = true; } }
                    break;
                }
                if (!isset($byId[$cursor])) { break; }
                $seen[$cursor] = true; $cursor = (int) ($byId[$cursor]->parent_id ?? 0);
            }
        }
        $reachable = [];
        $children = [];
        foreach ($byId as $id => $item) { $children[(int) ($item->parent_id ?? 0)][] = $id; }
        $frontier = $children[0] ?? [];
        for ($depth = 0; $depth < 100 && $frontier; $depth++) {
            $next = [];
            foreach ($frontier as $id) {
                if (isset($reachable[$id])) { continue; }
                $reachable[$id] = true;
                foreach ($children[$id] ?? [] as $child) { $next[] = $child; }
            }
            $frontier = $next;
        }
        $unreachable = [];
        foreach (array_keys($byId) as $id) { if (!isset($reachable[$id])) { $unreachable[] = $id; } }
        sort($selfParent); sort($missingParent); $cycleIds = array_keys($cycles); sort($cycleIds); sort($unreachable);
        return ['self_parent' => $selfParent, 'cycles' => $cycleIds, 'missing_parent' => $missingParent, 'unreachable' => $unreachable];
    }

    public static function flattenWithDepth(array $items): array
    {
        return self::order($items);
    }
}
