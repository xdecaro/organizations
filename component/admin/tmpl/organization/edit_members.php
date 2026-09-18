<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$organizationId = (int) ($this->item->id ?? 0);
$active = [];
$scheduled = [];
$history = [];

foreach ($this->appointments as $appointment) {
    $visualStatus = (string) ($appointment->visual_status ?? '');

    if ($visualStatus === 'active') {
        $active[] = $appointment;
    } elseif ($visualStatus === 'scheduled') {
        $scheduled[] = $appointment;
    } else {
        $history[] = $appointment;
    }
}

$statusKeys = [
    'active' => 'COM_XDECAROORGANIZATIONS_STATUS_ACTIVE',
    'scheduled' => 'COM_XDECAROORGANIZATIONS_STATUS_SCHEDULED',
    'expired' => 'COM_XDECAROORGANIZATIONS_STATUS_EXPIRED',
    'ended' => 'COM_XDECAROORGANIZATIONS_STATUS_ENDED',
    'resigned' => 'COM_XDECAROORGANIZATIONS_STATUS_RESIGNED',
    'revoked' => 'COM_XDECAROORGANIZATIONS_STATUS_REVOKED',
    'forfeited' => 'COM_XDECAROORGANIZATIONS_STATUS_FORFEITED',
];

$membershipStatusKeys = [
    'eligible' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_ELIGIBLE',
    'not_member' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_NOT_MEMBER',
    'inactive_member' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_INACTIVE',
    'fee_not_current' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_FEE_NOT_CURRENT',
    'unavailable' => 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_UNAVAILABLE',
];

$membershipBadgeClasses = [
    'eligible' => 'text-bg-success',
    'not_member' => 'text-bg-danger',
    'inactive_member' => 'text-bg-warning',
    'fee_not_current' => 'text-bg-warning',
    'unavailable' => 'text-bg-secondary',
];

$membershipRequirementKey = 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_REQUIREMENT_' . strtoupper(
    (string) ($this->appointmentMembershipRequirement ?: 'none')
);

$roleText = static function ($appointment): string {
    if (($appointment->role_code ?? '') === 'custom') {
        return trim((string) ($appointment->role_custom ?? '')) ?: Text::_('COM_XDECAROORGANIZATIONS_ROLE_CUSTOM');
    }

    return Text::_((string) ($appointment->role_label_key ?? 'COM_XDECAROORGANIZATIONS_ROLE_CUSTOM'));
};

$appointmentJson = static function ($appointment): string {
    return htmlspecialchars((string) json_encode([
        'id' => (int) ($appointment->id ?? 0),
        'body_id' => (int) ($appointment->body_id ?? 0),
        'person_uuid' => (string) ($appointment->person_uuid ?? ''),
        'person_name_snapshot' => (string) ($appointment->person_name_snapshot ?? ''),
        'role_code' => (string) ($appointment->role_code ?? ''),
        'role_custom' => (string) ($appointment->role_custom ?? ''),
        'starts_on' => (string) ($appointment->starts_on ?? ''),
        'planned_ends_on' => (string) ($appointment->planned_ends_on ?? ''),
        'notes' => (string) ($appointment->notes ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
};
?>
<div
    class="xdecaro-members"
    data-organization-id="<?php echo $organizationId; ?>"
    data-membership-requirement="<?php echo $this->escape((string) $this->appointmentMembershipRequirement); ?>"
>
    <?php if ($organizationId < 1) : ?>
        <div class="alert alert-info mb-0">
            <?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_SAVE_FIRST'); ?>
        </div>
    <?php else : ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h3 class="h5 mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_ACTIVE'); ?></h3>
                <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_ACTIVE_DESC'); ?></p>
            </div>
            <?php if ($this->canCreateAppointments) : ?>
                <button
                    type="button"
                    class="btn btn-primary"
                    data-appointment-add
                    <?php echo $this->peopleAvailable ? '' : 'disabled'; ?>
                >
                    <?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ADD'); ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (!$this->peopleAvailable) : ?>
            <div class="alert alert-warning">
                <?php echo Text::_('COM_XDECAROORGANIZATIONS_PEOPLE_UNAVAILABLE'); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->appointmentMembershipRequirement !== 'none') : ?>
            <div class="alert alert-info">
                <strong><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_REQUIREMENT'); ?>:</strong>
                <?php echo Text::_($membershipRequirementKey); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive mb-4">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_PERSON'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ROLE'); ?></th>
                        <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_BODY'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_MANDATE'); ?></th>
                        <th><?php echo Text::_('JSTATUS'); ?></th>
                        <th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($active === []) : ?>
                        <tr><td colspan="6" class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_ACTIVE_NONE'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($active as $appointment) : ?>
                        <tr>
                            <td>
                                <?php echo $this->escape((string) $appointment->person_name_snapshot); ?>
                                <?php if ($this->appointmentMembershipRequirement !== 'none') : ?>
                                    <?php
                                    $eligibility = is_array($appointment->membership_eligibility ?? null)
                                        ? $appointment->membership_eligibility
                                        : ['status' => 'unavailable'];
                                    $eligibilityStatus = (string) ($eligibility['status'] ?? 'unavailable');
                                    ?>
                                    <span
                                        class="badge <?php echo $membershipBadgeClasses[$eligibilityStatus] ?? 'text-bg-secondary'; ?> d-block mt-1 text-wrap"
                                        data-membership-status="<?php echo $this->escape($eligibilityStatus); ?>"
                                    >
                                        <?php echo Text::_($membershipStatusKeys[$eligibilityStatus] ?? 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_UNAVAILABLE'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $this->escape($roleText($appointment)); ?></td>
                            <td class="d-none d-lg-table-cell"><?php echo $this->escape((string) ($appointment->body_name ?? '')); ?></td>
                            <td>
                                <?php echo $this->escape((string) $appointment->starts_on); ?>
                                →
                                <?php echo $this->escape((string) ($appointment->planned_ends_on ?: Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_OPEN_END'))); ?>
                            </td>
                            <td><?php echo Text::_($statusKeys[$appointment->visual_status] ?? 'COM_XDECAROORGANIZATIONS_STATUS_ACTIVE'); ?></td>
                            <td class="text-end">
                                <?php if ($this->canEditAppointments || $this->canDeleteAppointments) : ?>
                                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                                        <?php if ($this->canEditAppointments) : ?>
                                            <button type="button" class="btn btn-outline-secondary" data-appointment-edit data-appointment="<?php echo $appointmentJson($appointment); ?>">
                                                <?php echo Text::_('JACTION_EDIT'); ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" data-appointment-end data-end-reason="term_end" data-appointment="<?php echo $appointmentJson($appointment); ?>">
                                                <?php echo Text::_('COM_XDECAROORGANIZATIONS_END_TERM'); ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" data-appointment-end data-end-reason="resignation" data-appointment="<?php echo $appointmentJson($appointment); ?>">
                                                <?php echo Text::_('COM_XDECAROORGANIZATIONS_END_RESIGNATION'); ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" data-appointment-end data-end-reason="revocation" data-appointment="<?php echo $appointmentJson($appointment); ?>">
                                                <?php echo Text::_('COM_XDECAROORGANIZATIONS_END_REVOCATION'); ?>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" data-appointment-end data-end-reason="forfeiture" data-appointment="<?php echo $appointmentJson($appointment); ?>">
                                                <?php echo Text::_('COM_XDECAROORGANIZATIONS_END_FORFEITURE'); ?>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($this->canDeleteAppointments) : ?>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger"
                                                data-appointment-delete
                                                data-appointment-id="<?php echo (int) $appointment->id; ?>"
                                                data-confirm="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_DELETE_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                <?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_DELETE'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_SCHEDULED'); ?></h3>
        <div class="table-responsive mb-4">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_PERSON'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ROLE'); ?></th>
                        <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_BODY'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_MANDATE'); ?></th>
                        <th><?php echo Text::_('JSTATUS'); ?></th>
                        <th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($scheduled === []) : ?>
                        <tr><td colspan="6" class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_SCHEDULED_NONE'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($scheduled as $appointment) : ?>
                        <tr>
                            <td>
                                <?php echo $this->escape((string) $appointment->person_name_snapshot); ?>
                                <?php if ($this->appointmentMembershipRequirement !== 'none') : ?>
                                    <?php
                                    $eligibility = is_array($appointment->membership_eligibility ?? null)
                                        ? $appointment->membership_eligibility
                                        : ['status' => 'unavailable'];
                                    $eligibilityStatus = (string) ($eligibility['status'] ?? 'unavailable');
                                    ?>
                                    <span
                                        class="badge <?php echo $membershipBadgeClasses[$eligibilityStatus] ?? 'text-bg-secondary'; ?> d-block mt-1 text-wrap"
                                        data-membership-status="<?php echo $this->escape($eligibilityStatus); ?>"
                                    >
                                        <?php echo Text::_($membershipStatusKeys[$eligibilityStatus] ?? 'COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_UNAVAILABLE'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $this->escape($roleText($appointment)); ?></td>
                            <td class="d-none d-lg-table-cell"><?php echo $this->escape((string) ($appointment->body_name ?? '')); ?></td>
                            <td>
                                <?php echo $this->escape((string) $appointment->starts_on); ?>
                                →
                                <?php echo $this->escape((string) ($appointment->planned_ends_on ?: Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_OPEN_END'))); ?>
                            </td>
                            <td><?php echo Text::_($statusKeys[$appointment->visual_status] ?? 'COM_XDECAROORGANIZATIONS_STATUS_SCHEDULED'); ?></td>
                            <td class="text-end">
                                <?php if ($this->canEditAppointments || $this->canDeleteAppointments) : ?>
                                    <div class="btn-group btn-group-sm flex-wrap" role="group">
                                        <?php if ($this->canEditAppointments) : ?>
                                            <button type="button" class="btn btn-outline-secondary" data-appointment-edit data-appointment="<?php echo $appointmentJson($appointment); ?>">
                                                <?php echo Text::_('JACTION_EDIT'); ?>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($this->canDeleteAppointments) : ?>
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger"
                                                data-appointment-delete
                                                data-appointment-id="<?php echo (int) $appointment->id; ?>"
                                                data-confirm="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_DELETE_CONFIRM'), ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                <?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_DELETE'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_HISTORY'); ?></h3>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_PERSON'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ROLE'); ?></th>
                        <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_BODY'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_MANDATE'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ENDED_ON'); ?></th>
                        <th><?php echo Text::_('JSTATUS'); ?></th>
                        <th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history === []) : ?>
                        <tr><td colspan="7" class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_MEMBERS_HISTORY_NONE'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($history as $appointment) : ?>
                        <tr>
                            <td><?php echo $this->escape((string) $appointment->person_name_snapshot); ?></td>
                            <td><?php echo $this->escape($roleText($appointment)); ?></td>
                            <td class="d-none d-lg-table-cell"><?php echo $this->escape((string) ($appointment->body_name ?? '')); ?></td>
                            <td>
                                <?php echo $this->escape((string) $appointment->starts_on); ?>
                                →
                                <?php echo $this->escape((string) ($appointment->planned_ends_on ?: Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_OPEN_END'))); ?>
                            </td>
                            <td><?php echo $this->escape((string) ($appointment->ended_on ?: '—')); ?></td>
                            <td><?php echo Text::_($statusKeys[$appointment->visual_status] ?? 'COM_XDECAROORGANIZATIONS_STATUS_ENDED'); ?></td>
                            <td class="text-end">
                                <?php if ($this->canEditAppointments) : ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-appointment-edit data-appointment="<?php echo $appointmentJson($appointment); ?>">
                                        <?php echo Text::_('JACTION_EDIT'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="modal fade" id="appointment-edit-modal" tabindex="-1" aria-labelledby="appointment-edit-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fs-5" id="appointment-edit-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_EDIT_TITLE'); ?></h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="appointment-id" value="0">
                        <input type="hidden" id="appointment-person-uuid" value="">

                        <div class="mb-3">
                            <label class="form-label" for="appointment-person-search"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_PERSON'); ?> *</label>
                            <input type="search" class="form-control" id="appointment-person-search" data-people-search autocomplete="off">
                            <div class="xdecaro-people-results list-group mt-1" data-people-results role="listbox"></div>
                            <div
                                class="alert mt-2 mb-0 d-none"
                                data-membership-eligibility
                                data-label-eligible="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_ELIGIBLE'), ENT_QUOTES, 'UTF-8'); ?>"
                                data-label-not-member="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_NOT_MEMBER'), ENT_QUOTES, 'UTF-8'); ?>"
                                data-label-inactive-member="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_INACTIVE'), ENT_QUOTES, 'UTF-8'); ?>"
                                data-label-fee-not-current="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_FEE_NOT_CURRENT'), ENT_QUOTES, 'UTF-8'); ?>"
                                data-label-unavailable="<?php echo htmlspecialchars(Text::_('COM_XDECAROORGANIZATIONS_MEMBERSHIP_ELIGIBILITY_UNAVAILABLE'), ENT_QUOTES, 'UTF-8'); ?>"
                            ></div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="appointment-body-id"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_BODY'); ?></label>
                                <select class="form-select" id="appointment-body-id">
                                    <option value="0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_BODY_NONE'); ?></option>
                                    <?php foreach ($this->bodies as $body) : ?>
                                        <option value="<?php echo (int) $body->id; ?>"><?php echo $this->escape((string) $body->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="appointment-role-code"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ROLE'); ?> *</label>
                                <select class="form-select" id="appointment-role-code">
                                    <option value="president"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_PRESIDENT'); ?></option>
                                    <option value="vice_president"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_VICE_PRESIDENT'); ?></option>
                                    <option value="secretary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_SECRETARY'); ?></option>
                                    <option value="treasurer"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_TREASURER'); ?></option>
                                    <option value="councillor"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_COUNCILLOR'); ?></option>
                                    <option value="auditor"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_AUDITOR'); ?></option>
                                    <option value="director"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_DIRECTOR'); ?></option>
                                    <option value="coordinator"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_COORDINATOR'); ?></option>
                                    <option value="representative"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_REPRESENTATIVE'); ?></option>
                                    <option value="commissioner"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_COMMISSIONER'); ?></option>
                                    <option value="vice_commissioner"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_VICE_COMMISSIONER'); ?></option>
                                    <option value="delegate"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_DELEGATE'); ?></option>
                                    <option value="control_member"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_CONTROL_MEMBER'); ?></option>
                                    <option value="administrative_secretary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_ADMINISTRATIVE_SECRETARY'); ?></option>
                                    <option value="custom"><?php echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_CUSTOM'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" data-role-custom-wrap>
                                <label class="form-label" for="appointment-role-custom"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ROLE_CUSTOM'); ?> *</label>
                                <input type="text" class="form-control" id="appointment-role-custom" maxlength="190">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="appointment-starts-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_STARTS_ON'); ?> *</label>
                                <input type="date" class="form-control" id="appointment-starts-on">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="appointment-duration"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_DURATION'); ?></label>
                                <select class="form-select" id="appointment-duration" data-duration-years>
                                    <option value="1"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_DURATION_YEARS', 1); ?></option>
                                    <option value="2"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_DURATION_YEARS', 2); ?></option>
                                    <option value="3"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_DURATION_YEARS', 3); ?></option>
                                    <option value="4"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_DURATION_YEARS', 4); ?></option>
                                    <option value="5"><?php echo Text::sprintf('COM_XDECAROORGANIZATIONS_DURATION_YEARS', 5); ?></option>
                                    <option value="custom"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DURATION_CUSTOM'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="appointment-planned-ends-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_PLANNED_ENDS_ON'); ?></label>
                                <input type="date" class="form-control" id="appointment-planned-ends-on">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label" for="appointment-notes"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_NOTES'); ?></label>
                            <textarea class="form-control" id="appointment-notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                        <button type="button" class="btn btn-primary" data-appointment-save><?php echo Text::_('JSAVE'); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="appointment-end-modal" tabindex="-1" aria-labelledby="appointment-end-title" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fs-5" id="appointment-end-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_END_TITLE'); ?></h3>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="appointment-end-id" value="0">
                        <input type="hidden" id="appointment-end-reason" value="">
                        <dl class="row mb-3">
                            <dt class="col-sm-4"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_PERSON'); ?></dt>
                            <dd class="col-sm-8" data-end-person></dd>
                            <dt class="col-sm-4"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ROLE'); ?></dt>
                            <dd class="col-sm-8" data-end-role></dd>
                        </dl>
                        <div class="mb-3">
                            <label class="form-label" for="appointment-ended-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ENDED_ON'); ?> *</label>
                            <input type="date" class="form-control" id="appointment-ended-on">
                        </div>
                        <div>
                            <label class="form-label" for="appointment-end-note"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_END_NOTE'); ?></label>
                            <textarea class="form-control" id="appointment-end-note" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                        <button type="button" class="btn btn-primary" data-appointment-end-save><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_CONFIRM_END'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>