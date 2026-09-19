<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$organizationId = (int) ($this->item->id ?? 0);
$statusKeys = [
    'active' => 'COM_XDECAROORGANIZATIONS_DELEGATION_STATUS_ACTIVE',
    'scheduled' => 'COM_XDECAROORGANIZATIONS_DELEGATION_STATUS_SCHEDULED',
    'ended' => 'COM_XDECAROORGANIZATIONS_DELEGATION_STATUS_ENDED',
    'inactive' => 'COM_XDECAROORGANIZATIONS_DELEGATION_STATUS_INACTIVE',
];

$roleText = static function ($appointment): string {
    if (($appointment->role_code ?? '') === 'custom') {
        return trim((string) ($appointment->role_custom ?? '')) ?: Text::_('COM_XDECAROORGANIZATIONS_ROLE_CUSTOM');
    }

    return Text::_((string) ($appointment->role_label_key ?? 'COM_XDECAROORGANIZATIONS_ROLE_CUSTOM'));
};

$delegationJson = static function ($delegation): string {
    return htmlspecialchars((string) json_encode([
        'id' => (int) ($delegation->id ?? 0),
        'organization_id' => (int) ($delegation->organization_id ?? 0),
        'appointment_id' => (int) ($delegation->appointment_id ?? 0),
        'title' => (string) ($delegation->title ?? ''),
        'scope' => (string) ($delegation->scope ?? ''),
        'starts_on' => (string) ($delegation->starts_on ?? ''),
        'ends_on' => (string) ($delegation->ends_on ?? ''),
        'effective_end' => (string) ($delegation->effective_end ?? ''),
        'notes' => (string) ($delegation->notes ?? ''),
        'state' => (int) ($delegation->state ?? 1),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
};

$appointmentLabel = static function ($appointment) use ($roleText): string {
    $parts = [
        trim((string) ($appointment->person_name_snapshot ?? '')),
        $roleText($appointment),
    ];

    if (trim((string) ($appointment->body_name ?? '')) !== '') {
        $parts[] = trim((string) $appointment->body_name);
    }

    return implode(' · ', array_filter($parts, static fn(string $part): bool => $part !== ''));
};
?>
<div class="xdecaro-delegations" data-organization-id="<?php echo $organizationId; ?>">
    <?php if ($organizationId < 1) : ?>
        <div class="alert alert-info mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATIONS_SAVE_FIRST'); ?></div>
    <?php else : ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h3 class="h5 mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATIONS_TITLE'); ?></h3>
                <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATIONS_DESC'); ?></p>
            </div>
            <?php if ($this->canCreateDelegations && $this->appointments !== []) : ?>
                <button type="button" class="btn btn-primary" data-delegation-add>
                    <?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_ADD'); ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if ($this->appointments === []) : ?>
            <div class="alert alert-warning"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATIONS_NEED_APPOINTMENT'); ?></div>
        <?php endif; ?>

        <?php if ($this->delegations === []) : ?>
            <div class="alert alert-light border"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATIONS_NONE'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_TITLE'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_HOLDER'); ?></th>
                            <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_BODY'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_PERIOD'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_STATUS'); ?></th>
                            <?php if ($this->canEditDelegations) : ?>
                                <th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ACTIONS'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->delegations as $delegation) : ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?php echo $this->escape((string) $delegation->title); ?></span>
                                    <?php if (trim((string) ($delegation->scope ?? '')) !== '') : ?>
                                        <span class="d-block small text-body-secondary"><?php echo $this->escape((string) $delegation->scope); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $this->escape((string) $delegation->person_name_snapshot); ?>
                                    <span class="d-block small text-body-secondary">
                                        <?php
                                        if (($delegation->role_code ?? '') === 'custom') {
                                            echo $this->escape((string) ($delegation->role_custom ?: Text::_('COM_XDECAROORGANIZATIONS_ROLE_CUSTOM')));
                                        } else {
                                            echo Text::_('COM_XDECAROORGANIZATIONS_ROLE_' . strtoupper((string) $delegation->role_code));
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo $this->escape((string) ($delegation->body_name ?? '')); ?></td>
                                <td>
                                    <?php echo $this->escape((string) $delegation->starts_on); ?>
                                    →
                                    <?php if (trim((string) ($delegation->ends_on ?? '')) !== '') : ?>
                                        <?php echo $this->escape((string) $delegation->ends_on); ?>
                                    <?php elseif (trim((string) ($delegation->effective_end ?? '')) !== '') : ?>
                                        <?php echo $this->escape((string) $delegation->effective_end); ?>
                                        <span class="d-block small text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_MANDATE_LIMIT'); ?></span>
                                    <?php else : ?>
                                        <?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_OPEN_END'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo Text::_($statusKeys[(string) ($delegation->visual_status ?? 'inactive')] ?? 'COM_XDECAROORGANIZATIONS_DELEGATION_STATUS_INACTIVE'); ?></td>
                                <?php if ($this->canEditDelegations) : ?>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-delegation-edit data-delegation="<?php echo $delegationJson($delegation); ?>">
                                            <?php echo Text::_('JACTION_EDIT'); ?>
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (($this->canCreateDelegations || $this->canEditDelegations) && $this->appointments !== []) : ?>
            <div class="modal fade" id="delegation-edit-modal" tabindex="-1" aria-labelledby="delegation-edit-title" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title fs-5" id="delegation-edit-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_EDIT_TITLE'); ?></h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="delegation-id" value="0">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="delegation-appointment-id"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_HOLDER'); ?> *</label>
                                    <select class="form-select" id="delegation-appointment-id">
                                        <option value="0"><?php echo Text::_('JSELECT'); ?></option>
                                        <?php foreach ($this->appointments as $appointment) : ?>
                                            <option
                                                value="<?php echo (int) $appointment->id; ?>"
                                                data-starts-on="<?php echo $this->escape((string) ($appointment->starts_on ?? '')); ?>"
                                                data-planned-ends-on="<?php echo $this->escape((string) ($appointment->planned_ends_on ?? '')); ?>"
                                                data-ended-on="<?php echo $this->escape((string) ($appointment->ended_on ?? '')); ?>"
                                            >
                                                <?php echo $this->escape($appointmentLabel($appointment)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-8">
                                    <label class="form-label" for="delegation-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_TITLE'); ?> *</label>
                                    <input type="text" class="form-control" id="delegation-title" maxlength="190">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="delegation-state"><?php echo Text::_('JSTATUS'); ?></label>
                                    <select class="form-select" id="delegation-state">
                                        <option value="1"><?php echo Text::_('JPUBLISHED'); ?></option>
                                        <option value="0"><?php echo Text::_('JUNPUBLISHED'); ?></option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="delegation-scope"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_SCOPE'); ?></label>
                                    <textarea class="form-control" id="delegation-scope" rows="3"></textarea>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="delegation-starts-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_STARTS_ON'); ?> *</label>
                                    <input type="date" class="form-control" id="delegation-starts-on">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="delegation-ends-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_ENDS_ON'); ?></label>
                                    <input type="date" class="form-control" id="delegation-ends-on">
                                    <div class="form-text" data-delegation-mandate-limit></div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="delegation-notes"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_NOTES'); ?></label>
                                    <textarea class="form-control" id="delegation-notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                            <button type="button" class="btn btn-primary" data-delegation-save><?php echo Text::_('JSAVE'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
