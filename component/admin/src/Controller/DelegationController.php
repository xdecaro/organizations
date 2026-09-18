<?php

namespace xdecaro\Component\Organizations\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Throwable;

final class DelegationController extends BaseController
{
    protected $option = 'com_xdecaroorganizations';

    public function save(): void
    {
        if (!Session::checkToken('post')) {
            $this->respond(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app = Factory::getApplication();
        $input = $app->getInput();
        $id = $input->post->getInt('id', 0);
        $user = $app->getIdentity();
        $allowed = $id > 0
            ? $user->authorise('core.edit', 'com_xdecaroorganizations')
            : $user->authorise('core.create', 'com_xdecaroorganizations');

        if (!$allowed && !$user->authorise('core.admin', 'com_xdecaroorganizations')) {
            $this->respond(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            return;
        }

        try {
            $model = $this->getModel('OrganizationDelegation', 'Administrator', ['ignore_request' => true]);
            $delegationId = $model->saveDelegation([
                'id' => $id,
                'organization_id' => $input->post->getInt('organization_id', 0),
                'appointment_id' => $input->post->getInt('appointment_id', 0),
                'title' => $input->post->getString('title', ''),
                'scope' => $input->post->getString('scope', ''),
                'starts_on' => $input->post->getString('starts_on', ''),
                'ends_on' => $input->post->getString('ends_on', ''),
                'notes' => $input->post->getString('notes', ''),
                'state' => $input->post->getInt('state', 1),
            ]);
            $this->respond(['id' => $delegationId], Text::_('COM_XDECAROORGANIZATIONS_DELEGATION_SAVED'));
        } catch (Throwable $e) {
            $this->respond(null, $e->getMessage(), true);
        }
    }

    private function respond(mixed $data = null, string $message = '', bool $error = false): void
    {
        echo new JsonResponse($data, $message, $error, true);
        Factory::getApplication()->close();
    }
}
