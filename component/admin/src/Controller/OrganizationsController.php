<?php

namespace xdecaro\Component\Organizations\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;

final class OrganizationsController extends AdminController
{
    protected $option = 'com_xdecaroorganizations';

    public function getModel($name = 'Organization', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
