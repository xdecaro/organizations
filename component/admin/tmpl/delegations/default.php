<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$e = fn($v): string => $this->escape((string) $v);
$search = (string) $this->state->get('filter.search', '');
$organization = (int) $this->state->get('filter.organization', 0);
$visual = (string) $this->state->get('filter.visual_status', '');
$temporal = (string) $this->state->get('filter.temporal', '');
$limit = (int) $this->state->get('list.limit', 20);
$openUrl = static fn($id): string => Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $id . '&activeTab=delegations');
$formatDate = static fn($value): string => trim((string) $value) === '' ? '' : HTMLHelper::_('date', (string) $value, 'd/m/Y');
$statusLabel = static fn(string $value): string => $value === 'active'
    ? Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIVE')
    : Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_INACTIVE');
$statusBadgeClass = static fn(string $value): string => $value === 'active' ? 'text-bg-success' : 'text-bg-secondary';
$period = static function ($item) use ($formatDate): string {
    $start = $formatDate($item->starts_on ?? '');
    $end = $formatDate($item->effective_end ?? '');
    return trim($start . (($start !== '' || $end !== '') ? ' → ' : '') . $end);
};
?>
<div class="xdecaro-global-list-page">
  <p class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_DELEGATIONS_DESC'); ?></p>
  <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=delegations'); ?>" method="get" id="adminForm" name="adminForm">
    <input type="hidden" name="option" value="com_xdecaroorganizations"><input type="hidden" name="view" value="delegations">
    <div class="xdecaro-global-list-filterbar" role="search">
      <div><label class="form-label" for="filter_search"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH'); ?></label><input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo $e($search); ?>"></div>
      <div><label class="form-label" for="filter_organization"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'); ?></label><select class="form-select" id="filter_organization" name="filter_organization"><option value="0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($this->organizationOptions as $o) : ?><option value="<?php echo (int) $o->id; ?>"<?php echo $organization === (int) $o->id ? ' selected' : ''; ?>><?php echo $e($o->name); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_visual_status"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></label><select class="form-select" id="filter_visual_status" name="filter_visual_status"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><option value="active"<?php echo $visual === 'active' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIVE'); ?></option><option value="inactive"<?php echo $visual === 'inactive' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_INACTIVE'); ?></option></select></div>
      <div><label class="form-label" for="filter_temporal"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERIOD'); ?></label><select class="form-select" id="filter_temporal" name="filter_temporal"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><option value="current"<?php echo $temporal === 'current' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CURRENT'); ?></option><option value="expired"<?php echo $temporal === 'expired' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_EXPIRED'); ?></option><option value="expiring"<?php echo $temporal === 'expiring' ? ' selected' : ''; ?>><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_EXPIRING_30'); ?></option></select><div class="form-text"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_EXPIRING_30_HELP'); ?></div></div>
      <div><label class="form-label" for="limit"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label><select class="form-select" id="limit" name="limit"><?php foreach ([10,20,50,100,0] as $n) : ?><option value="<?php echo $n; ?>"<?php echo $limit === $n ? ' selected' : ''; ?>><?php echo $n === 0 ? Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ALL') : $n; ?></option><?php endforeach; ?></select></div>
      <div class="d-flex gap-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_FILTER'); ?></button><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=delegations'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CLEAR'); ?></a></div>
    </div>
    <?php if (!$this->items) : ?><div class="alert alert-info xdecaro-global-list-empty"><?php echo Text::_($search !== '' || $organization || $visual !== '' || $temporal !== '' ? 'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER_EMPTY' : 'COM_XDECAROORGANIZATIONS_GLOBAL_NO_RECORDS'); ?></div><?php else : ?>
    <div class="table-responsive xdecaro-global-list-table-wrap"><table class="table table-striped align-middle xdecaro-global-list-table"><thead><tr><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_DELEGATION'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERSON'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ROLE_BODY'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERIOD'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></th><th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIONS'); ?></th></tr></thead><tbody>
      <?php foreach ($this->items as $item) : ?><tr><td class="fw-semibold"><?php echo $e($item->title); ?></td><td><?php echo $e($item->person_name_snapshot); ?></td><td><?php echo $e($item->organization_name); ?></td><td><?php echo $e(($item->role_custom ?: Text::_($item->role_label_key)) . (($item->body_name ?? '') !== '' ? ' · ' . $item->body_name : '')); ?></td><td><?php echo $e($period($item)); ?></td><td><span class="badge <?php echo $statusBadgeClass((string) $item->visual_status); ?>"><?php echo $e($statusLabel((string) $item->visual_status)); ?></span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="xdecaro-global-list-cards"><?php foreach ($this->items as $item) : ?><article class="xdecaro-global-list-card">
      <?php foreach ([[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_DELEGATION'),$item->title],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERSON'),$item->person_name_snapshot],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'),$item->organization_name],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ROLE_BODY'),($item->role_custom ?: Text::_($item->role_label_key)) . (($item->body_name ?? '') !== '' ? ' · ' . $item->body_name : '')],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERIOD'),$period($item)]] as [$label,$value]) : ?><div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo $e($label); ?></span><span class="xdecaro-global-list-card__value"><?php echo $e($value); ?></span></div><?php endforeach; ?>
      <div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></span><span class="xdecaro-global-list-card__value"><span class="badge <?php echo $statusBadgeClass((string) $item->visual_status); ?>"><?php echo $e($statusLabel((string) $item->visual_status)); ?></span></span></div>
      <div class="xdecaro-global-list-card__actions"><a class="btn btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></div>
    </article><?php endforeach; ?></div>
    <div class="mt-3"><?php echo $this->pagination->getListFooter(); ?></div><?php endif; ?>
  </form>
</div>
