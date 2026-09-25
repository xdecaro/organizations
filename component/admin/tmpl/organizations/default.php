<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.multiselect');
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

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" name="checkall-toggle" onclick="Joomla.checkAll(this)"></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_NAME'); ?></th>
                        <th class="d-none d-md-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_COLUMN_ACRONYM'); ?></th>
                        <th class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_TYPE'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_STRUCTURE_LEVEL'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_OPERATIONAL_STATUS'); ?></th>
                        <th class="d-none d-xl-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELD_PARENT'); ?></th>
                        <th><?php echo Text::_('JSTATUS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php $depth = max(0, (int) ($item->hierarchy_depth ?? 0)); ?>
                        <tr>
                            <td><?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?></td>
                            <td>
                                <div class="xdecaro-organization-tree-name" style="--xdecaro-org-indent-desktop: <?php echo $depth * 1.5; ?>rem; --xdecaro-org-indent-mobile: <?php echo $depth * 1; ?>rem;">
                                    <?php if ($depth > 0) : ?>
                                        <span class="xdecaro-organization-tree-branch" aria-hidden="true">↳</span>
                                    <?php endif; ?>
                                    <a href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $item->id); ?>"><?php echo $this->escape($item->name); ?></a>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell"><?php echo $this->escape((string) ($item->code ?? '')); ?></td>
                            <td class="d-none d-lg-table-cell"><?php echo Text::_('COM_XDECAROORGANIZATIONS_TYPE_' . strtoupper((string) $item->type)); ?></td>
                            <td><?php echo Text::_('COM_XDECAROORGANIZATIONS_STRUCTURE_' . strtoupper((string) ($item->structure_level ?: 'unspecified'))); ?></td>
                            <td><?php echo Text::_('COM_XDECAROORGANIZATIONS_OPERATIONAL_' . strtoupper((string) ($item->operational_status ?: 'active'))); ?></td>
                            <td class="d-none d-xl-table-cell"><?php echo $this->escape((string) $item->parent_name); ?></td>
                            <td><?php echo (int) $item->state === 1 ? Text::_('JPUBLISHED') : ((int) $item->state === -2 ? Text::_('JTRASHED') : Text::_('JUNPUBLISHED')); ?></td>
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
