<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');

$canChange = Factory::getApplication()->getIdentity()->authorise('core.edit.state', 'com_xdecaroorganizations');
$columns = [
    'name' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_NAME'),
    'code' => Text::_('COM_XDECAROORGANIZATIONS_COLUMN_ACRONYM'),
    'type' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_TYPE'),
    'structure' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_STRUCTURE_LEVEL'),
    'operational' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_OPERATIONAL_STATUS'),
    'affiliations' => Text::_('COM_XDECAROORGANIZATIONS_COLUMN_AFFILIATIONS'),
    'parent' => Text::_('COM_XDECAROORGANIZATIONS_FIELD_PARENT'),
    'state' => Text::_('JSTATUS'),
];
?>
<form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=organizations'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="xdecaro-scope">
        <div class="row g-2 mb-3 xdecaro-filter-toolbar">
            <div class="col-12 col-lg-5">
                <input type="search" name="filter_search" class="form-control w-100" value="<?php echo $this->escape((string) $this->state->get('filter.search')); ?>" placeholder="<?php echo Text::_('JSEARCH_FILTER'); ?>">
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <select name="filter_type" class="form-select" onchange="this.form.submit()">
                    <option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_ALL_TYPES'); ?></option>
                    <?php foreach (['organization', 'association', 'club', 'federation', 'company', 'public_body', 'school', 'sponsor', 'supplier'] as $type) : ?>
                        <option value="<?php echo $type; ?>" <?php echo (string) $this->state->get('filter.type') === $type ? 'selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_' . strtoupper($type)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-3 col-lg-2">
                <select name="filter_state" class="form-select" onchange="this.form.submit()">
                    <option value=""><?php echo Text::_('JOPTION_SELECT_PUBLISHED'); ?></option>
                    <option value="1" <?php echo (string) $this->state->get('filter.state') === '1' ? 'selected' : ''; ?>><?php echo Text::_('JPUBLISHED'); ?></option>
                    <option value="0" <?php echo (string) $this->state->get('filter.state') === '0' ? 'selected' : ''; ?>><?php echo Text::_('JUNPUBLISHED'); ?></option>
                    <option value="-2" <?php echo (string) $this->state->get('filter.state') === '-2' ? 'selected' : ''; ?>><?php echo Text::_('JTRASHED'); ?></option>
                </select>
            </div>
            <div class="col-12 col-sm-3 col-lg-2">
                <button class="btn btn-primary w-100" type="submit"><?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-2">
            <div class="dropdown">
                <button
                    class="btn btn-outline-secondary dropdown-toggle"
                    type="button"
                    data-bs-toggle="dropdown"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                >
                    <span class="icon-list" aria-hidden="true"></span>
                    <?php echo Text::_('COM_XDECAROORGANIZATIONS_COLUMNS'); ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3 xdecaro-column-picker" data-org-column-picker>
                    <div class="fw-semibold mb-2"><?php echo Text::_('COM_XDECAROORGANIZATIONS_COLUMNS_CHOOSE'); ?></div>
                    <?php foreach ($columns as $column => $label) : ?>
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                value="<?php echo $this->escape($column); ?>"
                                id="org-column-<?php echo $this->escape($column); ?>"
                                data-org-column-toggle
                                <?php echo $column === 'name' ? 'checked disabled' : 'checked'; ?>
                            >
                            <label class="form-check-label" for="org-column-<?php echo $this->escape($column); ?>">
                                <?php echo $this->escape($label); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="btn btn-sm btn-link px-0 mt-2" data-org-columns-reset>
                        <?php echo Text::_('COM_XDECAROORGANIZATIONS_COLUMNS_RESET'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" name="checkall-toggle" onclick="Joomla.checkAll(this)"></th>
                        <th data-org-column="name"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_NAME'); ?></th>
                        <th class="d-none d-md-table-cell" data-org-column="code"><?php echo Text::_('COM_XDECAROORGANIZATIONS_COLUMN_ACRONYM'); ?></th>
                        <th class="d-none d-lg-table-cell" data-org-column="type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_TYPE'); ?></th>
                        <th data-org-column="structure"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_STRUCTURE_LEVEL'); ?></th>
                        <th data-org-column="operational"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_OPERATIONAL_STATUS'); ?></th>
                        <th class="text-center" data-org-column="affiliations"><?php echo Text::_('COM_XDECAROORGANIZATIONS_COLUMN_AFFILIATIONS'); ?></th>
                        <th class="d-none d-xl-table-cell" data-org-column="parent"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_PARENT'); ?></th>
                        <th class="text-center" data-org-column="state"><?php echo Text::_('JSTATUS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php $depth = max(0, (int) ($item->hierarchy_depth ?? 0)); ?>
                        <tr>
                            <td><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                            <td data-org-column="name">
                                <div class="xdecaro-organization-tree-name" style="--xdecaro-org-indent-desktop: <?php echo $depth * 1.5; ?>rem; --xdecaro-org-indent-mobile: <?php echo $depth * 1; ?>rem;">
                                    <?php if ($depth > 0) : ?>
                                        <span class="xdecaro-organization-tree-branch" aria-hidden="true">↳</span>
                                    <?php endif; ?>
                                    <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->name); ?></a>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell" data-org-column="code"><?php echo $this->escape((string) ($item->code ?? '')); ?></td>
                            <td class="d-none d-lg-table-cell" data-org-column="type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_' . strtoupper((string) $item->type)); ?></td>
                            <td data-org-column="structure"><?php echo Text::_('COM_XDECAROORGANIZATIONS_STRUCTURE_' . strtoupper((string) ($item->structure_level ?: 'unspecified'))); ?></td>
                            <td data-org-column="operational"><?php echo Text::_('COM_XDECAROORGANIZATIONS_OPERATIONAL_' . strtoupper((string) ($item->operational_status ?: 'active'))); ?></td>
                            <td class="text-center" data-org-column="affiliations">
                                <span class="badge text-bg-light border"><?php echo (int) ($item->affiliation_count ?? 0); ?></span>
                            </td>
                            <td class="d-none d-xl-table-cell" data-org-column="parent"><?php echo $this->escape((string) $item->parent_name); ?></td>
                            <td class="text-center" data-org-column="state">
                                <?php echo HTMLHelper::_('jgrid.published', (int) $item->state, $i, 'organizations.', $canChange, 'cb'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php echo $this->pagination->getListFooter(); ?>
    </div>
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
