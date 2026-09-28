<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$e = fn($v): string => $this->escape((string) $v);
$listOrder = (string) $this->state->get('list.ordering', 'a.person_name_snapshot');
$listDirn = (string) $this->state->get('list.direction', 'ASC');
$search = (string) $this->state->get('filter.search', '');
$organization = (int) $this->state->get('filter.organization', 0);
$body = (int) $this->state->get('filter.body', 0);
$role = (string) $this->state->get('filter.role', '');
$visual = (string) $this->state->get('filter.visual_status', '');
$limit = (int) $this->state->get('list.limit', 20);
$openUrl = static fn($id): string => Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $id . '&activeTab=members');
$endValue = static fn($item): string => (string) (($item->ended_on ?? '') ?: ($item->planned_ends_on ?? ''));
$formatDate = static fn($value): string => trim((string) $value) === '' ? '' : HTMLHelper::_('date', (string) $value, 'd/m/Y');
$statusLabel = static function (string $value): string {
    return match ($value) {
        'active' => Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIVE'),
        'scheduled' => Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SCHEDULED'),
        'expired' => Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_EXPIRED'),
        default => Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ENDED'),
    };
};
$statusBadgeClass = static fn(string $value): string => match ($value) {
    'active' => 'text-bg-success',
    'scheduled' => 'text-bg-info',
    'expired' => 'text-bg-secondary',
    default => 'text-bg-secondary',
};
?>
<div class="xdecaro-global-list-page">
  <p class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_APPOINTMENTS_DESC'); ?></p>
  <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=appointments'); ?>" method="get" id="adminForm" name="adminForm">
    <input type="hidden" name="option" value="com_xdecaroorganizations"><input type="hidden" name="view" value="appointments">
    <input type="hidden" name="list[ordering]" value="<?php echo $e($listOrder); ?>"><input type="hidden" name="list[direction]" value="<?php echo $e($listDirn); ?>">
    <div class="xdecaro-global-list-filterbar" role="search">
      <div><label class="form-label" for="filter_search"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH'); ?></label><input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo $e($search); ?>"></div>
      <div><label class="form-label" for="filter_organization"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'); ?></label><select class="form-select" id="filter_organization" name="filter_organization"><option value="0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($this->organizationOptions as $o) : ?><option value="<?php echo (int) $o->id; ?>"<?php echo $organization === (int) $o->id ? ' selected' : ''; ?>><?php echo $e($o->name); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_body"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_BODY'); ?></label><select class="form-select" id="filter_body" name="filter_body"><option value="0"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($this->bodyOptions as $o) : ?><option value="<?php echo (int) $o->id; ?>"<?php echo $body === (int) $o->id ? ' selected' : ''; ?>><?php echo $e($o->organization_name . ' · ' . $o->name); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_role"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ROLE'); ?></label><select class="form-select" id="filter_role" name="filter_role"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($this->roleOptions as $r) : ?><option value="<?php echo $e($r); ?>"<?php echo $role === $r ? ' selected' : ''; ?>><?php echo $e(Text::_('COM_XDECAROORGANIZATIONS_ROLE_' . strtoupper($r))); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_visual_status"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></label><select class="form-select" id="filter_visual_status" name="filter_visual_status"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach (['active','scheduled','expired','ended'] as $s) : ?><option value="<?php echo $s; ?>"<?php echo $visual === $s ? ' selected' : ''; ?>><?php echo $e($statusLabel($s)); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="limit"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label><select class="form-select" id="limit" name="limit"><?php foreach ([10,20,50,100,0] as $n) : ?><option value="<?php echo $n; ?>"<?php echo $limit === $n ? ' selected' : ''; ?>><?php echo $n === 0 ? Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ALL') : $n; ?></option><?php endforeach; ?></select></div>
      <div class="d-flex gap-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_FILTER'); ?></button><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=appointments'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CLEAR'); ?></a></div>
    </div>
    <?php if (!$this->items) : ?><div class="alert alert-info xdecaro-global-list-empty"><?php echo Text::_($search !== '' || $organization || $body || $role !== '' || $visual !== '' ? 'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER_EMPTY' : 'COM_XDECAROORGANIZATIONS_GLOBAL_NO_RECORDS'); ?></div><?php else : ?>
    <div class="table-responsive xdecaro-global-list-table-wrap"><table class="table table-striped align-middle xdecaro-global-list-table"><thead><tr>
      <th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_PERSON', 'a.person_name_snapshot', $listDirn, $listOrder); ?></th><th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION', 'o.name', $listDirn, $listOrder); ?></th><th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_BODY', 'b.name', $listDirn, $listOrder); ?></th><th><?php echo HTMLHelper::_('searchtools.sort', 'COM_XDECAROORGANIZATIONS_GLOBAL_ROLE', 'a.role_code', $listDirn, $listOrder); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_START'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_END'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></th><th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIONS'); ?></th>
    </tr></thead><tbody><?php foreach ($this->items as $item) : ?><tr>
      <td><span class="fw-semibold"><?php echo $e($item->person_name_snapshot); ?></span><small class="d-block text-body-secondary"><?php echo $e($item->person_uuid); ?></small></td><td><?php echo $e($item->organization_name); ?></td><td><?php echo $e($item->body_name ?? ''); ?></td><td><?php echo $e($item->role_custom ?: Text::_($item->role_label_key)); ?></td><td><?php echo $e($formatDate($item->starts_on)); ?></td><td><?php echo $e($formatDate($endValue($item))); ?></td><td><span class="badge <?php echo $statusBadgeClass((string) $item->visual_status); ?>"><?php echo $e($statusLabel((string) $item->visual_status)); ?></span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></td>
    </tr><?php endforeach; ?></tbody></table></div>
    <div class="xdecaro-global-list-cards"><?php foreach ($this->items as $item) : ?><article class="xdecaro-global-list-card">
      <?php foreach ([[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_PERSON'),$item->person_name_snapshot],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'),$item->organization_name],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_BODY'),$item->body_name ?? ''],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ROLE'),$item->role_custom ?: Text::_($item->role_label_key)],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_START'),$formatDate($item->starts_on)],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_END'),$formatDate($endValue($item))]] as [$label,$value]) : ?><div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo $e($label); ?></span><span class="xdecaro-global-list-card__value"><?php echo $e($value); ?></span></div><?php endforeach; ?>
      <div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></span><span class="xdecaro-global-list-card__value"><span class="badge <?php echo $statusBadgeClass((string) $item->visual_status); ?>"><?php echo $e($statusLabel((string) $item->visual_status)); ?></span></span></div>
      <div class="xdecaro-global-list-card__actions"><a class="btn btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->organization_id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></div>
    </article><?php endforeach; ?></div>
    <div class="mt-3"><?php echo $this->pagination->getListFooter(); ?></div><?php endif; ?>
  </form>
</div>
