<?php
namespace xdecaro\Component\Organizations\Administrator\Model;
defined('_JEXEC') or die;use Joomla\CMS\Factory;use Joomla\CMS\MVC\Model\BaseDatabaseModel;use xdecaro\Component\Organizations\Administrator\Extension\OrganizationsComponent;final class DuplicatesModel extends BaseDatabaseModel{public function getGroups():array{$c=Factory::getApplication()->bootComponent('com_xdecaroorganizations');return $c instanceof OrganizationsComponent?$c->getDuplicateService()->find():[];}}
