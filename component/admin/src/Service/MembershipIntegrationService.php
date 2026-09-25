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

            if (!is_object($component)) {
                return $this->cache[$cacheKey] = $this->unavailable();
            }

            if (method_exists($component, 'getMembershipEligibilityService')) {
                return $this->cache[$cacheKey] = $this->evaluateCurrentContract(
                    $component->getMembershipEligibilityService(),
                    $personUuid,
                    $requirement
                );
            }

            if (method_exists($component, 'getMembershipPersonHistoryService')) {
                return $this->cache[$cacheKey] = $this->evaluateLegacyContract(
                    $component->getMembershipPersonHistoryService(),
                    $personUuid,
                    $requirement
                );
            }

            return $this->cache[$cacheKey] = $this->unavailable();
        } catch (Throwable) {
            return $this->cache[$cacheKey] = $this->unavailable();
        }
    }

    private function evaluateCurrentContract(object $service, string $personUuid, string $requirement): array
    {
        if (!method_exists($service, 'getSnapshot')) {
            return $this->unavailable();
        }

        $snapshot = $service->getSnapshot($personUuid);

        if (!is_array($snapshot) || $snapshot === []) {
            return [
                'available' => true,
                'eligible' => false,
                'status' => 'not_member',
                'member' => null,
            ];
        }

        if (empty($snapshot['is_active_member'])) {
            return [
                'available' => true,
                'eligible' => false,
                'status' => 'inactive_member',
                'member' => $this->memberSummary($snapshot),
            ];
        }

        if ($requirement === 'active_member_fee_current') {
            if (!method_exists($service, 'isFeeCurrent')) {
                return $this->unavailable();
            }

            $memberId = (int) ($snapshot['id'] ?? $snapshot['member_id'] ?? 0);
            if ($memberId < 1) {
                return $this->unavailable();
            }

            $associationYear = Factory::getDate()->format('Y');
            if (!$service->isFeeCurrent($memberId, $associationYear)) {
                return [
                    'available' => true,
                    'eligible' => false,
                    'status' => 'fee_not_current',
                    'member' => $this->memberSummary($snapshot),
                ];
            }
        }

        return [
            'available' => true,
            'eligible' => true,
            'status' => 'eligible',
            'member' => $this->memberSummary($snapshot),
        ];
    }

    private function evaluateLegacyContract(object $service, string $personUuid, string $requirement): array
    {
        if (!method_exists($service, 'getHistoryByPersonUuid')) {
            return $this->unavailable();
        }

        $history = $service->getHistoryByPersonUuid($personUuid);
        if (!is_array($history) || $history === [] || empty($history['current'])) {
            return [
                'available' => true,
                'eligible' => false,
                'status' => 'not_member',
                'member' => null,
            ];
        }

        $eligibility = is_array($history['eligibility'] ?? null) ? $history['eligibility'] : [];
        $current = is_array($history['current'] ?? null) ? $history['current'] : [];

        if (empty($eligibility['active'])) {
            return [
                'available' => true,
                'eligible' => false,
                'status' => 'inactive_member',
                'member' => $this->memberSummary($current),
            ];
        }

        if ($requirement === 'active_member_fee_current' && empty($eligibility['fee_current'])) {
            return [
                'available' => true,
                'eligible' => false,
                'status' => 'fee_not_current',
                'member' => $this->memberSummary($current),
            ];
        }

        return [
            'available' => true,
            'eligible' => true,
            'status' => 'eligible',
            'member' => $this->memberSummary($current),
        ];
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
            'member_id' => (int) ($current['member_id'] ?? $current['id'] ?? 0),
            'member_number' => (string) ($current['member_number'] ?? ''),
            'card_number' => (string) ($current['card_number'] ?? ''),
            'category_name' => (string) ($current['category_name'] ?? ''),
            'location_name' => (string) ($current['location_name'] ?? ''),
            'status' => (string) ($current['status'] ?? ''),
        ];
    }
}
