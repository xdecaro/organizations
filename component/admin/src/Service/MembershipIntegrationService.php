<?php

namespace xdecaro\Component\Organizations\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Throwable;

final class MembershipIntegrationService
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    public function evaluate(string $personUuid, string $requirement): array
    {
        $personUuid = strtolower(trim($personUuid));
        $requirement = AppointmentMembershipPolicyService::normalizeRequirement($requirement);

        if ($requirement === 'none' || $requirement === 'inherit') {
            return [
                'available' => true,
                'eligible' => true,
                'status' => 'not_required',
                'member' => null,
            ];
        }

        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $personUuid)) {
            return [
                'available' => true,
                'eligible' => false,
                'status' => 'not_member',
                'member' => null,
            ];
        }

        $cacheKey = $personUuid . '|' . $requirement;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $component = Factory::getApplication()->bootComponent('com_decaromembership');

            if (!is_object($component) || !method_exists($component, 'getMembershipPersonHistoryService')) {
                return $this->cache[$cacheKey] = $this->unavailable();
            }

            $service = $component->getMembershipPersonHistoryService();
            if (!is_object($service) || !method_exists($service, 'getHistoryByPersonUuid')) {
                return $this->cache[$cacheKey] = $this->unavailable();
            }

            $history = $service->getHistoryByPersonUuid($personUuid);
            if (!is_array($history) || $history === [] || empty($history['current'])) {
                return $this->cache[$cacheKey] = [
                    'available' => true,
                    'eligible' => false,
                    'status' => 'not_member',
                    'member' => null,
                ];
            }

            $eligibility = is_array($history['eligibility'] ?? null) ? $history['eligibility'] : [];
            $current = is_array($history['current'] ?? null) ? $history['current'] : [];

            if (empty($eligibility['active'])) {
                return $this->cache[$cacheKey] = [
                    'available' => true,
                    'eligible' => false,
                    'status' => 'inactive_member',
                    'member' => $this->memberSummary($current),
                ];
            }

            if ($requirement === 'active_member_fee_current' && empty($eligibility['fee_current'])) {
                return $this->cache[$cacheKey] = [
                    'available' => true,
                    'eligible' => false,
                    'status' => 'fee_not_current',
                    'member' => $this->memberSummary($current),
                ];
            }

            return $this->cache[$cacheKey] = [
                'available' => true,
                'eligible' => true,
                'status' => 'eligible',
                'member' => $this->memberSummary($current),
            ];
        } catch (Throwable) {
            return $this->cache[$cacheKey] = $this->unavailable();
        }
    }

    private function unavailable(): array
    {
        return [
            'available' => false,
            'eligible' => null,
            'status' => 'unavailable',
            'member' => null,
        ];
    }

    private function memberSummary(array $current): array
    {
        return [
            'member_id' => (int) ($current['member_id'] ?? 0),
            'member_number' => (string) ($current['member_number'] ?? ''),
            'card_number' => (string) ($current['card_number'] ?? ''),
            'category_name' => (string) ($current['category_name'] ?? ''),
            'location_name' => (string) ($current['location_name'] ?? ''),
            'status' => (string) ($current['status'] ?? ''),
        ];
    }
}
