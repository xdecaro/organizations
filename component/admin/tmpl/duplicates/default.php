<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$typeLabels = [
    'name' => 'COM_XDECAROORGANIZATIONS_DUPLICATE_FIELD_NAME',
    'legal_name' => 'COM_XDECAROORGANIZATIONS_DUPLICATE_FIELD_LEGAL_NAME',
    'code' => 'COM_XDECAROORGANIZATIONS_DUPLICATE_FIELD_CODE',
    'email' => 'COM_XDECAROORGANIZATIONS_DUPLICATE_FIELD_EMAIL',
    'pec_email' => 'COM_XDECAROORGANIZATIONS_DUPLICATE_FIELD_PEC_EMAIL',
    'vat_id' => 'COM_XDECAROORGANIZATIONS_DUPLICATE_FIELD_VAT_ID',
    'tax_identifier' => 'COM_XDECAROORGANIZATIONS_DUPLICATE_FIELD_TAX_IDENTIFIER',
];
?>
<div class="xdecaro-scope">
    <div class="alert alert-info"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DUPLICATES_HELP'); ?></div>

    <?php if (!$this->groups) : ?>
        <div class="alert alert-light border"><?php echo Text::_('COM_XDECAROORGANIZATIONS_DUPLICATES_NONE'); ?></div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_DUPLICATE_TYPE'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_DUPLICATE_KEY'); ?></th>
                        <th><?php echo Text::_('COM_XDECAROORGANIZATIONS_DUPLICATE_COUNT'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->groups as $group) : ?>
                        <tr>
                            <td><?php echo Text::_($typeLabels[$group['type']] ?? $group['type']); ?></td>
                            <td><?php echo $this->escape($group['key']); ?></td>
                            <td><?php echo (int) $group['count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
