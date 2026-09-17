<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');

$organizationName = trim((string) ($this->item->name ?? ''));
$organizationHeading = $organizationName !== ''
    ? $this->escape($organizationName)
    : Text::_('COM_XDECAROORGANIZATIONS_ORGANIZATION_NEW');
$allowedTabs = ['identity', 'structure', 'contacts', 'bodies', 'members', 'publishing', 'system'];
$requestedTab = Factory::getApplication()->getInput()->getCmd('activeTab', 'identity');
$activeTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'identity';
?>
<form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="organization-form" class="form-validate">
    <div class="xdecaro-scope xdecaro-organizations-organization-edit">
        <div class="xdecaro-organization-heading">
            <h2><?php echo $organizationHeading; ?></h2>
        </div>
        <?php
        echo HTMLHelper::_('uitab.startTabSet', 'organizationTabs', ['active' => $activeTab]);

        echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'identity', Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_IDENTITY'));
        echo $this->form->renderFieldset('identity');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'structure', Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_STRUCTURE'));
        echo $this->form->renderFieldset('structure');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'contacts', Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_CONTACTS'));
        echo $this->form->renderFieldset('contacts');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'bodies', Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_BODIES'));
        echo $this->loadTemplate('bodies');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'members', Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_MEMBERS'));
        echo $this->loadTemplate('members');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING'));
        echo $this->form->renderFieldset('publishing');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.addTab', 'organizationTabs', 'system', Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_SYSTEM'));
        echo $this->form->renderFieldset('system');
        echo HTMLHelper::_('uitab.endTab');

        echo HTMLHelper::_('uitab.endTabSet');
        ?>
    </div>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
