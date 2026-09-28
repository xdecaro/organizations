<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$e = fn($v): string => $this->escape((string) $v);
$search = (string) $this->state->get('filter.search', '');
$organization = (int) $this->state->get('filter.organization', 0);
$bodyType = (string) $this->state->get('filter.body_type', '');
$visual = (string) $this->state->get('filter.visual_status', '');
$limit = (int) $this->state->get('list.limit', 20);
$openUrl = static fn($id): string => Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $id . '&activeTab=bodies');
?>
<div class="xdecaro-global-list-page">
  <p class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_BODIES_DESC'); ?></p>
  <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=bodies'); ?>" method="get" id="adminForm" name="adminForm">
    <input type="hidden" name="option" value="com_xdecaroorganizations"><input type="hidden" name="view" value="bodies">
    <div class="xdecaro-global-list-filterbar" role="search">
      <div><label class="form-label" for="filter_search"><?php echo Text::_('JSEARCH_FILTER'); ?></label><input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo $e($search); ?>"></div>
      <div><label class="form-label" for="filter_organization"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'); ?></label><select class="form-select" id="filter_organization" name="filter_organization"><option value="0"><?php echo Text::_('JOPTION_SELECT_ALL'); ?></option><?php foreach ($this->organizationOptions as $o) : ?><option value="<?php echo (int) $o->id; ?>"<?php echo $organization === (int) $o->id ? ' selected' : ''; ?>><?php echo $e($o->name); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_body_type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TYPE'); ?></label><select class="form-select" id="filter_body_type" name="filter_body_type"><option value=""><?php echo Text::_('JOPTION_SELECT_ALL'); ?></option><?php foreach ($this->bodyTypeOptions as $v) : ?><option value="<?php echo $e($v); ?>"<?php echo $bodyType === $v ? ' selected' : ''; ?>><?php echo $e($v); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_visual_status"><?php echo Text::_('JSTATUS'); ?></label><select class="form-select" id="filter_visual_status" name="filter_visual_status"><option value=""><?php echo Text::_('JOPTION_SELECT_ALL'); ?></option><option value="active"<?php echo $visual === 'active' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIVE'); ?></option><option value="inactive"<?php echo $visual === 'inactive' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_INACTIVE'); ?></option></select></div>
      <div><label class="form-label" for="limit"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label><select class="form-select" id="limit" name="limit"><?php foreach ([10,20,50,100,0] as $n) : ?><option value="<?php echo $n; ?>"<?php echo $limit === $n ? ' selected' : ''; ?>><?php echo $n === 0 ? Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ALL') : $n; ?></option><?php endforeach; ?></select></div>
      <div class="d-flex gap-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('JFILTER'); ?></button><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=bodies'); ?>"><?php echo Text::_('JCLEAR'); ?></a></div>
    </div>
    <?php if (!$this->items) : ?><div class="alert alert-info xdecaro-global-list-empty"><?php echo Text::_($search !== '' || $organization || $bodyType !== '' || $visual !== '' ? 'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER_EMPTY' : 'COM_XDECAROORGANIZATIONS_GLOBAL_NO_RECORDS'); ?></div><?php else : ?>
    <div class="table-responsive xdecaro-global-list-table-wrap"><table class="table table-striped align-middle xdecaro-global-list-table"><thead><tr><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_BODY'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TYPE'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PARENT_BODY'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERIOD'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_APPOINTMENT_COUNT'); ?></th><th><?php echo Text::_('JSTATUS'); ?></th><th class="text-end"><?php echo Text::_('JACTIONS'); ?></th></tr></thead><tbody>
      <?php foreach ($this->items as $item) : ?><tr><td><span class="fw-semibold"><?php echo $e($item->name); ?></span><?php if (($item->code ?? '') !== '') : ?><small class="d-block text-body-secondary"><?php echo $e($item->code); ?></small><?php endif; ?></td><td><?php echo $e($item->organization_name); ?></td><td><?php echo $e(Text::_($item->type_label_key)); ?></td><td><?php echo $e($item->parent_name ?? ''); ?></td><td><?php echo $e(($item->starts_on ?? '') . (($item->starts_on ?? '') !== '' || ($item->ends_on ?? '') !== '' ? ' → ' : '') . ($item->ends_on ?? '')); ?></td><td><?php echo (int) $item->appointment_count; ?></td><td><?php echo $e((string) $item->visual_status); ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="xdecaro-global-list-cards"><?php foreach ($this->items as $item) : ?><article class="xdecaro-global-list-card"><div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_BODY'); ?></span><span class="xdecaro-global-list-card__value fw-semibold"><?php echo $e($item->name); ?></span></div><div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'); ?></span><span class="xdecaro-global-list-card__value"><?php echo $e($item->organization_name); ?></span></div><div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_APPOINTMENT_COUNT'); ?></span><span class="xdecaro-global-list-card__value"><?php echo (int) $item->appointment_count; ?></span></div><div class="xdecaro-global-list-card__actions"><a class="btn btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></div></article><?php endforeach; ?></div>
    <div class="mt-3"><?php echo $this->pagination->getListFooter(); ?></div><?php endif; ?>
  </form>
</div>
