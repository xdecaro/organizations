<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Throwable;

final class PeopleIntegrationService
{
    private ?object $provider = null;
    private bool $resolved = false;

    public function isAvailable(): bool
    {
        return $this->provider() !== null;
    }

    public function searchPeople(string $search, int $limit = 20): array
    {
        $provider = $this->provider();
        if (!$provider || !method_exists($provider, 'searchPeople')) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $filters = ['search' => trim($search)];

        try {
            return array_values((array) $provider->searchPeople(
                $filters,
                $limit,
                true
            ));
        } catch (Throwable) {
            try {
                return array_values((array) $provider->searchPeople(
                    $filters,
                    $limit,
                    false
                ));
            } catch (Throwable) {
                return [];
            }
        }
    }

    public function getPerson(string $uuid): ?array
    {
        $provider = $this->provider();
        if (!$provider || !method_exists($provider, 'getPerson')) {
            return null;
        }

        try {
            $person = $provider->getPerson(strtolower(trim($uuid)), false);
            return is_array($person) ? $person : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function getPeopleByUuids(array $uuids): array
    {
        $provider = $this->provider();
        if (!$provider || !method_exists($provider, 'getPeopleByUuids')) {
            return [];
        }

        try {
            return (array) $provider->getPeopleByUuids($uuids, false);
        } catch (Throwable) {
            return [];
        }
    }

    private function provider(): ?object
    {
        if ($this->resolved) {
            return $this->provider;
        }

        $this->resolved = true;

        try {
            $component = Factory::getApplication()->bootComponent('com_xdecaropeople');
            if (!method_exists($component, 'getPersonProviderService')) {
                return null;
            }

            $provider = $component->getPersonProviderService();
            if (!is_object($provider)) {
                return null;
            }

            return $this->provider = $provider;
        } catch (Throwable) {
            return null;
        }
    }
}
