<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$e = fn($v): string => $this->escape((string) $v);
$search = (string) $this->state->get('filter.search', '');
$type = (string) $this->state->get('filter.type', '');
$structure = (string) $this->state->get('filter.structure', '');
$operational = (string) $this->state->get('filter.operational', '');
$rootsOnly = (int) $this->state->get('filter.roots_only', 0);
$limit = (int) $this->state->get('list.limit', 20);
$diag = $this->diagnostics;
$warningIds = [];
foreach (['self_parent','cycles','missing_parent','unreachable'] as $key) { foreach ($diag[$key] ?? [] as $id) { $warningIds[(int) $id][$key] = true; } }
$typeLabel = static fn(string $value): string => Text::_('COM_XDECAROORGANIZATIONS_TYPE_' . strtoupper($value));
$structureLabel = static fn(string $value): string => Text::_('COM_XDECAROORGANIZATIONS_STRUCTURE_' . strtoupper($value));
$operationalLabel = static fn(string $value): string => Text::_('COM_XDECAROORGANIZATIONS_OPERATIONAL_' . strtoupper($value));
$operationalBadgeClass = static fn(string $value): string => match ($value) {
    'active' => 'text-bg-success',
    'represented' => 'text-bg-info',
    'commissaried' => 'text-bg-warning',
    default => 'text-bg-secondary',
};
$openUrl = static fn($id): string => Route::_('index.php?option=com_xdecaroorganizations&task=organization.edit&id=' . (int) $id . '&activeTab=hierarchy');
?>
<div class="xdecaro-global-list-page">
  <p class="text-body-secondary"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_HIERARCHY_DESC'); ?></p>
  <form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=hierarchy'); ?>" method="get" id="adminForm" name="adminForm">
    <input type="hidden" name="option" value="com_xdecaroorganizations"><input type="hidden" name="view" value="hierarchy">
    <div class="xdecaro-global-list-filterbar" role="search">
      <div><label class="form-label" for="filter_search"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SEARCH'); ?></label><input class="form-control" type="search" id="filter_search" name="filter_search" value="<?php echo $e($search); ?>"></div>
      <div><label class="form-label" for="filter_type"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TYPE'); ?></label><select class="form-select" id="filter_type" name="filter_type"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($this->typeOptions as $v) : ?><option value="<?php echo $e($v); ?>"<?php echo $type === $v ? ' selected' : ''; ?>><?php echo $e($typeLabel($v)); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_structure"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STRUCTURE'); ?></label><select class="form-select" id="filter_structure" name="filter_structure"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($this->structureOptions as $v) : ?><option value="<?php echo $e($v); ?>"<?php echo $structure === $v ? ' selected' : ''; ?>><?php echo $e($structureLabel($v)); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_operational"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPERATIONAL'); ?></label><select class="form-select" id="filter_operational" name="filter_operational"><option value=""><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_SELECT_ALL'); ?></option><?php foreach ($this->operationalOptions as $v) : ?><option value="<?php echo $e($v); ?>"<?php echo $operational === $v ? ' selected' : ''; ?>><?php echo $e($operationalLabel($v)); ?></option><?php endforeach; ?></select></div>
      <div><label class="form-label" for="filter_roots_only"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ROOTS_ONLY'); ?></label><select class="form-select" id="filter_roots_only" name="filter_roots_only"><option value="0"><?php echo Text::_('JNO'); ?></option><option value="1"<?php echo $rootsOnly === 1 ? ' selected' : ''; ?>><?php echo Text::_('JYES'); ?></option></select></div>
      <div><label class="form-label" for="limit"><?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?></label><select class="form-select" id="limit" name="limit"><?php foreach ([10,20,50,100,0] as $n) : ?><option value="<?php echo $n; ?>"<?php echo $limit === $n ? ' selected' : ''; ?>><?php echo $n === 0 ? Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ALL') : $n; ?></option><?php endforeach; ?></select></div>
      <div class="d-flex gap-2"><button class="btn btn-primary" type="submit"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_FILTER'); ?></button><a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_xdecaroorganizations&view=hierarchy'); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CLEAR'); ?></a></div>
    </div>
    <?php if (!$this->items) : ?><div class="alert alert-info xdecaro-global-list-empty"><?php echo Text::_($search !== '' || $type !== '' || $structure !== '' || $operational !== '' || $rootsOnly ? 'COM_XDECAROORGANIZATIONS_GLOBAL_FILTER_EMPTY' : 'COM_XDECAROORGANIZATIONS_GLOBAL_NO_RECORDS'); ?></div><?php else : ?>
    <div class="table-responsive xdecaro-global-list-table-wrap"><table class="table table-striped align-middle xdecaro-global-list-table"><thead><tr><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TYPE'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STRUCTURE'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPERATIONAL'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CHILDREN'); ?></th><th><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></th><th class="text-end"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ACTIONS'); ?></th></tr></thead><tbody>
      <?php foreach ($this->items as $item) : $depth=(int)($item->hierarchy_depth ?? 0); $warnings=$warningIds[(int)$item->id] ?? []; ?><tr><td><div class="xdecaro-hierarchy-depth" style="--xdecaro-depth:<?php echo min($depth, 8); ?>"><span class="fw-semibold"><?php echo $e($item->name); ?></span><?php if (($item->code ?? '') !== '') : ?><small class="d-block text-body-secondary"><?php echo $e($item->code); ?></small><?php endif; ?></div></td><td><?php echo $e($typeLabel((string) $item->type)); ?></td><td><?php echo $e($structureLabel((string) $item->structure_level)); ?></td><td><span class="badge <?php echo $operationalBadgeClass((string) $item->operational_status); ?>"><?php echo $e($operationalLabel((string) $item->operational_status)); ?></span></td><td><?php echo (int)($item->child_count ?? 0); ?></td><td><?php if ($warnings) : ?><span class="badge text-bg-warning xdecaro-hierarchy-warning"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_HIERARCHY_WARNING'); ?></span><?php else : ?><span class="badge text-bg-success"><?php echo Text::_('JOK'); ?></span><?php endif; ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <div class="xdecaro-global-list-cards"><?php foreach ($this->items as $item) : $depth=(int)($item->hierarchy_depth ?? 0); $warnings=$warningIds[(int)$item->id] ?? []; ?><article class="xdecaro-global-list-card xdecaro-hierarchy-depth" style="--xdecaro-depth:<?php echo min($depth, 4); ?>">
      <?php foreach ([[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_ORGANIZATION'),$item->name],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_TYPE'),$typeLabel((string)$item->type)],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STRUCTURE'),$structureLabel((string)$item->structure_level)],[Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_CHILDREN'),(string)($item->child_count ?? 0)]] as [$label,$value]) : ?><div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo $e($label); ?></span><span class="xdecaro-global-list-card__value"><?php echo $e($value); ?></span></div><?php endforeach; ?>
      <div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPERATIONAL'); ?></span><span class="xdecaro-global-list-card__value"><span class="badge <?php echo $operationalBadgeClass((string) $item->operational_status); ?>"><?php echo $e($operationalLabel((string) $item->operational_status)); ?></span></span></div>
      <div class="xdecaro-global-list-card__row"><span class="xdecaro-global-list-card__label"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_STATUS'); ?></span><span class="xdecaro-global-list-card__value"><?php if ($warnings) : ?><span class="badge text-bg-warning xdecaro-hierarchy-warning"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_HIERARCHY_WARNING'); ?></span><?php else : ?><span class="badge text-bg-success"><?php echo Text::_('JOK'); ?></span><?php endif; ?></span></div>
      <div class="xdecaro-global-list-card__actions"><a class="btn btn-outline-primary xdecaro-global-list-open" href="<?php echo $openUrl($item->id); ?>"><?php echo Text::_('COM_XDECAROORGANIZATIONS_GLOBAL_OPEN'); ?></a></div>
    </article><?php endforeach; ?></div>
    <div class="mt-3"><?php echo $this->pagination->getListFooter(); ?></div><?php endif; ?>
  </form>
</div>
