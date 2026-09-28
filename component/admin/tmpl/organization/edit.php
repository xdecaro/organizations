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
$allowedTabs = ['identity', 'structure', 'hierarchy', 'affiliations', 'contacts', 'bodies', 'members', 'delegations', 'publishing', 'system'];
$requestedTab = Factory::getApplication()->getInput()->getCmd('activeTab', 'identity');
$activeTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'identity';

ob_start();
?>
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_CONTACTS'); ?></h4>
                <?php echo $this->form->renderFieldset('contacts'); ?>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_SOCIAL'); ?></h4>
                <?php echo $this->form->renderFieldset('social'); ?>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="h5 mb-3"><?php echo Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_HEADQUARTERS'); ?></h4>
                <?php echo $this->form->renderFieldset('headquarters'); ?>
            </div>
        </div>
    </div>
</div>
<?php
$contactsContent = (string) ob_get_clean();

$sections = [
    'identity' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_IDENTITY'),
        'content' => $this->form->renderFieldset('identity'),
    ],
    'structure' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_STRUCTURE'),
        'content' => $this->form->renderFieldset('structure'),
    ],
    'hierarchy' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_HIERARCHY'),
        'content' => $this->loadTemplate('hierarchy'),
    ],
    'affiliations' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_AFFILIATIONS'),
        'content' => $this->loadTemplate('affiliations'),
    ],
    'contacts' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_CONTACTS'),
        'content' => $contactsContent,
    ],
    'bodies' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_BODIES'),
        'content' => $this->loadTemplate('bodies'),
    ],
    'members' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_MEMBERS'),
        'content' => $this->loadTemplate('members'),
    ],
    'delegations' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_DELEGATIONS'),
        'content' => $this->loadTemplate('delegations'),
    ],
    'publishing' => [
        'label' => Text::_('JGLOBAL_FIELDSET_PUBLISHING'),
        'content' => $this->form->renderFieldset('publishing'),
    ],
    'system' => [
        'label' => Text::_('COM_XDECAROORGANIZATIONS_FIELDSET_SYSTEM'),
        'content' => $this->loadTemplate('system'),
    ],
];
?>
<form action="<?php echo Route::_('index.php?option=com_xdecaroorganizations&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="organization-form" class="form-validate">
    <div class="xdecaro-scope xdecaro-organizations-organization-edit">
        <div class="xdecaro-organization-heading">
            <h2><?php echo $organizationHeading; ?></h2>
        </div>

        <div class="accordion xdecaro-organization-accordion" id="organizationAccordion">
            <?php foreach ($sections as $sectionId => $section) : ?>
                <?php
                $isOpen = $activeTab === $sectionId;
                $headingId = 'organizationAccordionHeading-' . $sectionId;
                $panelId = 'organizationAccordionPanel-' . $sectionId;
                ?>
                <section class="accordion-item">
                    <h3 class="accordion-header" id="<?php echo $headingId; ?>">
                        <button
                            class="accordion-button<?php echo $isOpen ? '' : ' collapsed'; ?>"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?php echo $panelId; ?>"
                            aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo $panelId; ?>"
                        >
                            <?php echo $section['label']; ?>
                        </button>
                    </h3>
                    <div
                        id="<?php echo $panelId; ?>"
                        class="accordion-collapse collapse<?php echo $isOpen ? ' show' : ''; ?>"
                        aria-labelledby="<?php echo $headingId; ?>"
                        data-bs-parent="#organizationAccordion"
                        data-organization-section="<?php echo $sectionId; ?>"
                    >
                        <div class="accordion-body">
                            <?php echo $section['content']; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
    <input type="hidden" name="activeTab" id="organization-active-tab" value="<?php echo $this->escape($activeTab); ?>">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
