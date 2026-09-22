<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$organizationId = (int) ($this->item->id ?? 0);
$statusKeys = [
    'active' => 'COM_XDECAROORGANIZATIONS_BODY_STATUS_ACTIVE',
    'scheduled' => 'COM_XDECAROORGANIZATIONS_BODY_STATUS_SCHEDULED',
    'ended' => 'COM_XDECAROORGANIZATIONS_BODY_STATUS_ENDED',
    'inactive' => 'COM_XDECAROORGANIZATIONS_BODY_STATUS_INACTIVE',
];

$bodyJson = static function ($body): string {
    return htmlspecialchars((string) json_encode([
        'id' => (int) ($body->id ?? 0),
        'organization_id' => (int) ($body->organization_id ?? 0),
        'parent_id' => (int) ($body->parent_id ?? 0),
        'name' => (string) ($body->name ?? ''),
        'code' => (string) ($body->code ?? ''),
        'body_type' => (string) ($body->body_type ?? 'other'),
        'starts_on' => (string) ($body->starts_on ?? ''),
        'ends_on' => (string) ($body->ends_on ?? ''),
        'notes' => (string) ($body->notes ?? ''),
        'state' => (int) ($body->state ?? 1),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
};
?>
<div class="xdecaro-bodies" data-organization-id="<?php echo $organizationId; ?>">
    <?php if ($organizationId < 1) : ?>
        <div class="alert alert-info mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODIES_SAVE_FIRST'); ?></div>
    <?php else : ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h3 class="h5 mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODIES_TITLE'); ?></h3>
                <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODIES_DESC'); ?></p>
            </div>
            <?php if ($this->canCreateBodies) : ?>
                <button type="button" class="btn btn-primary" data-body-add>
                    <?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_ADD'); ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (!$this->bodies) : ?>
            <div class="alert alert-light border"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODIES_NONE'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_NAME'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_TYPE'); ?></th>
                            <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_PARENT'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_STATUS'); ?></th>
                            <th class="d-none d-md-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_PERIOD'); ?></th>
                            <?php if ($this->canEditBodies || $this->canDeleteBodies) : ?>
                                <th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ACTIONS'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->bodies as $body) : ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?php echo $this->escape((string) $body->name); ?></span>
                                    <?php if (!empty($body->code)) : ?>
                                        <span class="d-block small text-body-secondary"><?php echo $this->escape((string) $body->code); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo Text::_((string) ($body->type_label_key ?? 'COM_XDECAROORGANIZATIONS_BODY_TYPE_OTHER')); ?></td>
                                <td class="d-none d-lg-table-cell"><?php echo $this->escape((string) ($body->parent_name ?? '')); ?></td>
                                <td><?php echo Text::_($statusKeys[(string) ($body->visual_status ?? 'inactive')] ?? 'COM_XDECAROORGANIZATIONS_BODY_STATUS_INACTIVE'); ?></td>
                                <td class="d-none d-md-table-cell">
                                    <?php
                                    $from = trim((string) ($body->starts_on ?? ''));
                                    $to = trim((string) ($body->ends_on ?? ''));
                                    echo $this->escape(trim($from . ($from !== '' || $to !== '' ? ' → ' : '') . $to));
                                    ?>
                                </td>
                                <?php if ($this->canEditBodies || $this->canDeleteBodies) : ?>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="<?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ACTIONS'); ?>">
                                            <?php if ($this->canEditBodies) : ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary"
                                                    data-body-edit
                                                    data-body="<?php echo $bodyJson($body); ?>"
                                                >
                                                    <?php echo Text::_('JACTION_EDIT'); ?>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($this->canDeleteBodies) : ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger"
                                                    data-body-delete
                                                    data-body-id="<?php echo (int) $body->id; ?>"
                                                    data-confirm="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_BODY_DELETE_CONFIRM')); ?>"
                                                >
                                                    <?php echo Text::_('JACTION_DELETE'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($this->canCreateBodies || $this->canEditBodies) : ?>
            <div class="modal fade" id="body-edit-modal" tabindex="-1" aria-labelledby="body-edit-title" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title fs-5" id="body-edit-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_EDIT_TITLE'); ?></h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="body-id" value="0">

                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label" for="body-name"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_NAME'); ?> *</label>
                                    <input type="text" class="form-control" id="body-name" maxlength="190">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="body-code"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_CODE'); ?></label>
                                    <input type="text" class="form-control" id="body-code" maxlength="100">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="body-type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_TYPE'); ?> *</label>
                                    <select class="form-select" id="body-type">
                                        <?php foreach ([
                                            'congress', 'assembly', 'board', 'presidency', 'secretariat',
                                            'control_body', 'audit_body', 'disciplinary_body', 'youth_body',
                                            'commission', 'committee', 'department', 'sector', 'office', 'other',
                                        ] as $type) : ?>
                                            <option value="<?php echo $type; ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_TYPE_' . strtoupper($type)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="body-parent-id"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_PARENT'); ?></label>
                                    <select class="form-select" id="body-parent-id">
                                        <option value="0"><?php echo Text::_('JNONE'); ?></option>
                                        <?php foreach ($this->bodies as $candidate) : ?>
                                            <option value="<?php echo (int) $candidate->id; ?>"><?php echo $this->escape((string) $candidate->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="body-starts-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_STARTS_ON'); ?></label>
                                    <input type="date" class="form-control" id="body-starts-on">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="body-ends-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_BODY_ENDS_ON'); ?></label>
                                    <input type="date" class="form-control" id="body-ends-on">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="body-state"><?php echo Text::_('JSTATUS'); ?></label>
                                    <select class="form-select" id="body-state">
                                        <option value="1"><?php echo Text::_('JPUBLISHED'); ?></option>
                                        <option value="0"><?php echo Text::_('JUNPUBLISHED'); ?></option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="body-notes"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_NOTES'); ?></label>
                                    <textarea class="form-control" id="body-notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                            <button type="button" class="btn btn-primary" data-body-save><?php echo Text::_('JSAVE'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
