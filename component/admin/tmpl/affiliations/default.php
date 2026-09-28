<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$escape = fn($value): string => $this->escape((string) $value);
$listOrder = (string) $this->state->get('list.ordering', 's.name');
$listDirn = (string) $this->state->get('list.direction', 'ASC');
$perspective = (string) $this->state->get('filter.perspective', 'all');
$source = (int) $this->state->get('filter.source', 0);
$target = (int) $this->state->get('filter.target', 0);
$relationType = (string) $this->state->get('filter.relation_type', '');
$status = (string) $this->state->get('filter.status', '');
$temporal = (string) $this->state->get('filter.temporal', '');
$search = (string) $this->state->get('filter.search', '');
$limit = (int) $this->state->get('list.limit', 20);
$types = ['sports_affiliation', 'institutional_affiliation', 'membership', 'recognition', 'other'];
$statuses = ['active', 'pending', 'suspended', 'expired', 'inactive'];
$formatDate = static fn($value): string => trim((string) $value) === '' ? '' : HTMLHelper::_('date', (string) $value, 'd/m/Y');
$period = static function ($item) use ($formatDate): string {
    $start = $formatDate($item->starts_on ?? '');
    $end = $formatDate($item->ends_on ?? '');
    return trim($start . (($start !== '' || $end !== '') ? ' → ' : '') . $end);
};
$statusBadgeClass = static fn(string $value): string => match ($value) {
    'active' => 'text-bg-success',
    'pending', 'suspended' => 'text-bg-warning',
    default => 'text-bg-secondary',
};
$openUrl = static fn($id): string => Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $id . '&activeTab=affiliations');
?>
<div class="xdecaro-global-list-page">
  <p class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_AFFILIATIONS_DESC'); ?></p>
  <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=affiliations'); ?>" method="get" id="adminForm" name="adminForm">
    <input type="hidden" name="option" value="com_xdecaroorganizations"><input type="hidden" name="view" value="affiliations">
    <input type="hidden" name="list[ordering]" value="<?php echo $escape($listOrder); ?>"><input type="hidden" name="list[direction]" value="<?php echo $escape($listDirn); ?>">
    <div class="xdecaro-global-list-filterbar" role="search">
      <div><label class="form-label" for="filter_search"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH'); ?></label><input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo $escape($search); ?>" placeholder="<?php echo $escape(Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH')); ?>"></div>
      <div><label class="form-label" for="filter_perspective"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERSPECTIVE'); ?></label><select class="form-select" id="filter_perspective" name="filter_perspective"><?php foreach (['all' => 'COM_XDECAROORGANIZATIONS_GLOBAL_ALL', 'affiliations' => 'COM_XDECAROORGANIZATIONS_GLOBAL_AFFILIATIONS_TITLE', 'affiliates' => 'COM_XDECAROORGANIZATIONS_GLOBAL_AFFILIATES'] as $value => $label) : ?><option value="<?php echo $value; ?>"<?php echo $perspective === $value ? ' selected' : ''; ?>><?php echo Text::_($label); ?></option><?php endforeach; ?></select></div>
      <div>
        <label class="form-label" for="filter_source"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SOURCE'); ?></label>
        <joomla-field-fancy-select search-placeholder="<?php echo $escape(Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH_ORGANIZATION')); ?>">
          <select class="form-select" id="filter_source" name="filter_source" data-xdecaro-auto-submit="true">
            <option value="0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option>
            <?php foreach ($this->sourceOptions as $option) : ?>
              <option value="<?php echo (int) $option->id; ?>"<?php echo $source === (int) $option->id ? ' selected' : ''; ?>><?php echo $escape($option->name . (!empty($option->code) ? ' · ' . $option->code : '')); ?></option>
            <?php endforeach; ?>
          </select>
        </joomla-field-fancy-select>
      </div>
      <div>
        <label class="form-label" for="filter_target"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TARGET'); ?></label>
        <joomla-field-fancy-select search-placeholder="<?php echo $escape(Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH_ORGANIZATION')); ?>">
          <select class="form-select" id="filter_target" name="filter_target" data-xdecaro-auto-submit="true">
            <option value="0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option>
            <?php foreach ($this->targetOptions as $option) : ?>
              <option value="<?php echo (int) $option->id; ?>"<?php echo $target === (int) $option->id ? ' selected' : ''; ?>><?php echo $escape($option->name . (!empty($option->code) ? ' · ' . $option->code : '')); ?></option>
            <?php endforeach; ?>
          </select>
        </joomla-field-fancy-select>
      </div>
      <div><label class="form-label" for="filter_relation_type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TYPE'); ?></label><select class="form-select" id="filter_relation_type" name="filter_relation_type"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($types as $value) : ?><option value="<?php echo $value; ?>"<?php echo $relationType === $value ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_TYPE_' . strtoupper($value)); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_status"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></label><select class="form-select" id="filter_status" name="filter_status"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($statuses as $value) : ?><option value="<?php echo $value; ?>"<?php echo $status === $value ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_STATUS_' . strtoupper($value)); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_temporal"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERIOD'); ?></label><select class="form-select" id="filter_temporal" name="filter_temporal"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><option value="current"<?php echo $temporal === 'current' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CURRENT'); ?></option><option value="expired"<?php echo $temporal === 'expired' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_EXPIRED'); ?></option></select></div>
      <div><label class="form-label" for="limit"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label><select class="form-select" id="limit" name="limit"><?php foreach ([10,20,50,100,0] as $n) : ?><option value="<?php echo $n; ?>"<?php echo $limit === $n ? ' selected' : ''; ?>><?php echo $n === 0 ? Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ALL') : $n; ?></option><?php endforeach; ?></select></div>
      <div class="d-flex gap-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_FILTER'); ?></button><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=affiliations'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CLEAR'); ?></a></div>
    </div>
    <?php if (!$this->items) : ?><div class="alert alert-info xdecaro-global-list-empty"><?php echo Text::_($search !== '' || $source || $target || $relationType !== '' || $status !== '' || $temporal !== '' ? 'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER_EMPTY' : 'COM_XDECAROORGANIZATIONS_GLOBAL_NO_RECORDS'); ?></div><?php else : ?>
      <div class="table-responsive xdecaro-global-list-table-wrap"><table class="table table-striped align-middle xdecaro-global-list-table"><thead><tr>
        <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_SOURCE', 's.name', $listDirn, $listOrder); ?></th><th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_TARGET', 't.name', $listDirn, $listOrder); ?></th><th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_TYPE', 'a.relation_type', $listDirn, $listOrder); ?></th><th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_STATUS', 'a.status', $listDirn, $listOrder); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERIOD'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CODE'); ?></th><th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIONS'); ?></th>
      </tr></thead><tbody><?php foreach ($this->items as $item) : ?><tr>
        <td><a href="<?php echo $openUrl($item->organization_id); ?>"><?php echo $escape($item->source_name); ?></a><?php if ($item->source_code ?? '') : ?><small class="d-block text-body-secondary"><?php echo $escape($item->source_code); ?></small><?php endif; ?></td>
        <td><a href="<?php echo $openUrl($item->target_organization_id); ?>"><?php echo $escape($item->target_name); ?></a><?php if ($item->target_code ?? '') : ?><small class="d-block text-body-secondary"><?php echo $escape($item->target_code); ?></small><?php endif; ?></td>
        <td><?php echo Text::_($item->type_label_key); ?></td><td><span class="badge <?php echo $statusBadgeClass((string) $item->status); ?>"><?php echo Text::_($item->status_label_key); ?></span></td><td><?php echo $escape($period($item)); ?></td><td><?php echo $escape($item->relation_code ?? ''); ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></td>
      </tr><?php endforeach; ?></tbody></table></div>
      <div class="xdecaro-global-list-cards"><?php foreach ($this->items as $item) : ?><article class="xdecaro-global-list-card">
        <?php foreach ([[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SOURCE'),$item->source_name],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TARGET'),$item->target_name],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TYPE'),Text::_($item->type_label_key)],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERIOD'),$period($item)],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CODE'),$item->relation_code ?? '']] as [$label,$value]) : ?><div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo $escape($label); ?></span><span class="xdecaro-global-list-card__value"><?php echo $escape($value); ?></span></div><?php endforeach; ?>
        <div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></span><span class="xdecaro-global-list-card__value"><span class="badge <?php echo $statusBadgeClass((string) $item->status); ?>"><?php echo Text::_($item->status_label_key); ?></span></span></div>
        <div class="xdecaro-global-list-card__actions"><a class="btn btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></div>
      </article><?php endforeach; ?></div>
      <div class="mt-3"><?php echo $this->pagination->getListFooter(); ?></div>
    <?php endif; ?>
  </form>
</div>
