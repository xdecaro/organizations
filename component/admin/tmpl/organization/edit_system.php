<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

$uuid = trim((string) ($this->item->uuid ?? ''));
$created = trim((string) ($this->item->created ?? ''));
$modified = trim((string) ($this->item->modified ?? ''));

$createdDisplay = $created !== ''
    ? HTMLHelper::_('date', $created, Text::_('DATE_FORMAT_LC2'), true)
    : Text::_('JNONE');
$modifiedDisplay = $modified !== ''
    ? HTMLHelper::_('date', $modified, Text::_('DATE_FORMAT_LC2'), true)
    : Text::_('JNONE');

$createdBy = trim($this->auditCreatedByName) !== '' ? $this->auditCreatedByName : Text::_('JNONE');
$modifiedBy = trim($this->auditModifiedByName) !== '' ? $this->auditModifiedByName : Text::_('JNONE');

$readonlyField = static function (string $label, string $value): string {
    return '<div class="control-group">'
        . '<div class="control-label"><label>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</label></div>'
        . '<div class="controls"><input type="text" class="form-control" value="'
        . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" readonly></div>'
        . '</div>';
};
?>
<div class="xdecaro-organization-system">
    <div class="alert alert-info mb-4">
        <?php echo Text::_('COM_XDECAROORGANIZATIONS_SYSTEM_AUDIT_DESC'); ?>
    </div>

    <?php echo $readonlyField(Text::_('COM_XDECAROORGANIZATIONS_FIELD_UUID'), $uuid !== '' ? $uuid : Text::_('JNONE')); ?>
    <?php echo $readonlyField(Text::_('COM_XDECAROORGANIZATIONS_FIELD_CREATED'), $createdDisplay); ?>
    <?php echo $readonlyField(Text::_('COM_XDECAROORGANIZATIONS_FIELD_CREATED_BY'), $createdBy); ?>
    <?php echo $readonlyField(Text::_('COM_XDECAROORGANIZATIONS_FIELD_MODIFIED_ORGANIZATION'), $modifiedDisplay); ?>
    <?php echo $readonlyField(Text::_('COM_XDECAROORGANIZATIONS_FIELD_MODIFIED_BY'), $modifiedBy); ?>
</div>
