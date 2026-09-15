<?php

namespace xdecaro\Component\Organizations\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use xdecaro\Component\Organizations\Administrator\Service\CountryMetadata;

final class CountryField extends ListField
{
    protected $type = 'Country';

    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        if (is_string($value)) {
            $value = strtoupper(trim($value));
        }

        return parent::setup($element, $value, $group);
    }

    protected function getOptions()
    {
        $options = parent::getOptions();
        $language = Factory::getApplication()->getLanguage();
        $languageTag = $language ? $language->getTag() : 'en-GB';

        foreach (CountryMetadata::countries($languageTag) as $country) {
            $options[] = HTMLHelper::_('select.option', $country['alpha2'], $country['name'] . ' — ' . $country['alpha2']);
        }

        return $options;
    }
}
