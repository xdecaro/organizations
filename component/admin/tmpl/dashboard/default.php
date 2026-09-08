<?php
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
$scope = $this->coreUiActive ? 'xdecaro-scope' : '';
?>
<div class="<?php echo $scope; ?> decaroorganizations-dashboard"><div class="<?php echo $this->coreUiActive ? 'xdecaro-card' : 'card'; ?>"><div class="<?php echo $this->coreUiActive ? 'xdecaro-card__body' : 'card-body'; ?>"><h2><?php echo Text::_('COM_DECAROORGANIZATIONS_DASHBOARD_TITLE'); ?></h2><p><?php echo Text::_('COM_DECAROORGANIZATIONS_DASHBOARD_INTRO'); ?></p><dl class="decaroorganizations-diagnostics"><dt><?php echo Text::_('COM_DECAROORGANIZATIONS_VERSION'); ?></dt><dd>0.1.0</dd><dt><?php echo Text::_('COM_DECAROORGANIZATIONS_CORE_VERSION'); ?></dt><dd><?php echo $this->coreVersion !== '' ? htmlspecialchars($this->coreVersion, ENT_QUOTES, 'UTF-8') : Text::_('COM_DECAROORGANIZATIONS_CORE_MISSING'); ?></dd></dl></div></div></div>
