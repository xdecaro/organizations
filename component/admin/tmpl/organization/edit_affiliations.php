<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$organizationId = (int) ($this->item->id ?? 0);

$affiliationJson = static function ($item): string {
    return htmlspecialchars((string) json_encode([
        'id' => (int) ($item->id ?? 0),
        'organization_id' => (int) ($item->organization_id ?? 0),
        'target_organization_id' => (int) ($item->target_organization_id ?? 0),
        'target_name' => (string) ($item->target_name ?? ''),
        'target_code' => (string) ($item->target_code ?? ''),
        'target_type' => (string) ($item->target_type ?? ''),
        'relation_type' => (string) ($item->relation_type ?? 'sports_affiliation'),
        'relation_code' => (string) ($item->relation_code ?? ''),
        'starts_on' => (string) ($item->starts_on ?? ''),
        'ends_on' => (string) ($item->ends_on ?? ''),
        'status' => (string) ($item->status ?? 'active'),
        'notes' => (string) ($item->notes ?? ''),
        'state' => (int) ($item->state ?? 1),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
};
?>
<div class="xdecaro-affiliations" data-organization-id="<?php echo $organizationId; ?>">
    <?php if ($organizationId < 1) : ?>
        <div class="alert alert-info mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATIONS_SAVE_FIRST'); ?></div>
    <?php else : ?>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h3 class="h5 mb-1"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATIONS_TITLE'); ?></h3>
                <p class="text-body-secondary mb-0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATIONS_DESC'); ?></p>
            </div>
            <?php if ($this->canCreateAffiliations) : ?>
                <button type="button" class="btn btn-primary" data-affiliation-add>
                    <?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_ADD'); ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (!$this->affiliations) : ?>
            <div class="alert alert-light border"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATIONS_NONE'); ?></div>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TARGET'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE'); ?></th>
                            <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS'); ?></th>
                            <th class="d-none d-md-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_PERIOD'); ?></th>
                            <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_CODE'); ?></th>
                            <?php if ($this->canEditAffiliations || $this->canDeleteAffiliations) : ?>
                                <th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ACTIONS'); ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->affiliations as $affiliation) : ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?php echo $this->escape((string) ($affiliation->target_name ?? '')); ?></span>
                                    <?php if (!empty($affiliation->target_code)) : ?>
                                        <span class="d-block small text-body-secondary"><?php echo $this->escape((string) $affiliation->target_code); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo Text::_((string) ($affiliation->type_label_key ?? 'COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_OTHER')); ?></td>
                                <td><?php echo Text::_((string) ($affiliation->status_label_key ?? 'COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_INACTIVE')); ?></td>
                                <td class="d-none d-md-table-cell">
                                    <?php
                                    $from = trim((string) ($affiliation->starts_on ?? ''));
                                    $to = trim((string) ($affiliation->ends_on ?? ''));
                                    echo $this->escape(trim($from . ($from !== '' || $to !== '' ? ' → ' : '') . $to));
                                    ?>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo $this->escape((string) ($affiliation->relation_code ?? '')); ?></td>
                                <?php if ($this->canEditAffiliations || $this->canDeleteAffiliations) : ?>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($this->canEditAffiliations) : ?>
                                                <button type="button" class="btn btn-outline-primary" data-affiliation-edit data-affiliation="<?php echo $affiliationJson($affiliation); ?>">
                                                    <?php echo Text::_('JACTION_EDIT'); ?>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($this->canDeleteAffiliations) : ?>
                                                <button type="button" class="btn btn-outline-danger" data-affiliation-delete data-affiliation-id="<?php echo (int) $affiliation->id; ?>" data-confirm="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_DELETE_CONFIRM')); ?>">
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

        <?php if ($this->canCreateAffiliations || $this->canEditAffiliations) : ?>
            <div class="modal fade" id="affiliation-edit-modal" tabindex="-1" aria-labelledby="affiliation-edit-title" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title fs-5" id="affiliation-edit-title"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_EDIT_TITLE'); ?></h3>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
                        </div>
                        <div class="modal-body p-4">
                            <input type="hidden" id="affiliation-id" value="0">
                            <div class="row gx-3 gy-4">
                                <div class="col-12">
                                    <label class="form-label" for="affiliation-target-search"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TARGET'); ?> *</label>
                                    <div class="xdecaro-affiliation-target-picker">
                                        <input type="hidden" id="affiliation-target" value="0">
                                        <input
                                            type="search"
                                            class="form-control"
                                            id="affiliation-target-search"
                                            data-affiliation-target-search
                                            data-required-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TARGET_REQUIRED')); ?>"
                                            autocomplete="off"
                                            role="combobox"
                                            aria-autocomplete="list"
                                            aria-expanded="false"
                                            aria-controls="affiliation-target-results"
                                            placeholder="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TARGET_SEARCH_PLACEHOLDER')); ?>"
                                        >
                                        <div
                                            class="list-group xdecaro-affiliation-target-results d-none"
                                            id="affiliation-target-results"
                                            data-affiliation-target-results
                                            data-empty-label="<?php echo $this->escape(Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TARGET_SEARCH_EMPTY')); ?>"
                                            role="listbox"
                                        ></div>
                                    </div>
                                    <div class="form-text"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TARGET_SEARCH_HELP'); ?></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="affiliation-type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE'); ?> *</label>
                                    <select class="form-select" id="affiliation-type">
                                        <option value="sports_affiliation"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_SPORTS_AFFILIATION'); ?></option>
                                        <option value="institutional_affiliation"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_INSTITUTIONAL_AFFILIATION'); ?></option>
                                        <option value="membership"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_MEMBERSHIP'); ?></option>
                                        <option value="recognition"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_RECOGNITION'); ?></option>
                                        <option value="other"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_OTHER'); ?></option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="affiliation-code"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_CODE'); ?></label>
                                    <input type="text" class="form-control" id="affiliation-code" maxlength="100">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="affiliation-starts-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STARTS_ON'); ?></label>
                                    <input type="date" class="form-control" id="affiliation-starts-on">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="affiliation-ends-on"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_ENDS_ON'); ?></label>
                                    <input type="date" class="form-control" id="affiliation-ends-on">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label" for="affiliation-status"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS'); ?></label>
                                    <select class="form-select" id="affiliation-status">
                                        <option value="active"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_ACTIVE'); ?></option>
                                        <option value="pending"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_PENDING'); ?></option>
                                        <option value="suspended"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_SUSPENDED'); ?></option>
                                        <option value="expired"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_EXPIRED'); ?></option>
                                        <option value="inactive"><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_INACTIVE'); ?></option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="affiliation-notes"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_NOTES'); ?></label>
                                    <textarea class="form-control" id="affiliation-notes" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                            <button type="button" class="btn btn-primary" data-affiliation-save><?php echo Text::_('JSAVE'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
