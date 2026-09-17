<?php

namespace xdecaro\Component\Organizations\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Throwable;

final class AppointmentController extends BaseController
{
    protected $option = 'com_xdecaroorganizations';

    public function searchPeople(): void
    {
        if (!$this->checkPostToken()) {
            return;
        }

        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.manage', 'com_xdecaroorganizations')
            && !$user->authorise('core.admin', 'com_xdecaroorganizations')) {
            $this->respond(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            return;
        }

        try {
            $input = Factory::getApplication()->getInput();
            $search = trim($input->post->getString('search', ''));
            if (mb_strlen($search) < 2) {
                $this->respond(['items' => []]);
                return;
            }

            $component = Factory::getApplication()->bootComponent('com_xdecaroorganizations');
            $rows = $component->getPeopleIntegrationService()->searchPeople($search, 20);
            $items = [];

            foreach ($rows as $row) {
                $uuid = strtolower(trim((string) ($row['uuid'] ?? '')));
                $name = $this->personName($row);
                if ($uuid !== '' && $name !== '') {
                    $items[] = [
                        'uuid' => $uuid,
                        'name' => $name,
                        'birth_date' => trim((string) ($row['birth_date'] ?? '')),
                        'birth_place' => trim((string) ($row['birth_place'] ?? '')),
                    ];
                }
            }

            $this->respond(['items' => $items]);
        } catch (Throwable $e) {
            $this->respond(null, $e->getMessage(), true);
        }
    }

    public function save(): void
    {
        if (!$this->checkPostToken()) {
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
            $duration = trim($input->post->getString('duration_years', ''));
            $data = [
                'id' => $id,
                'organization_id' => $input->post->getInt('organization_id', 0),
                'body_id' => $input->post->getInt('body_id', 0),
                'person_uuid' => $input->post->getString('person_uuid', ''),
                'role_code' => $input->post->getCmd('role_code', ''),
                'role_custom' => $input->post->getString('role_custom', ''),
                'starts_on' => $input->post->getString('starts_on', ''),
                'planned_ends_on' => $input->post->getString('planned_ends_on', ''),
                'duration_years' => $duration !== '' ? (int) $duration : null,
                'notes' => $input->post->getString('notes', ''),
            ];

            $model = $this->getModel('OrganizationAppointment', 'Administrator', ['ignore_request' => true]);
            $appointmentId = $model->saveAppointment($data);
            $this->respond(['id' => $appointmentId], Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_SAVED'));
        } catch (Throwable $e) {
            $this->respond(null, $e->getMessage(), true);
        }
    }

    public function end(): void
    {
        if (!$this->checkPostToken()) {
            return;
        }

        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.edit', 'com_xdecaroorganizations')
            && !$user->authorise('core.admin', 'com_xdecaroorganizations')) {
            $this->respond(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            return;
        }

        try {
            $input = Factory::getApplication()->getInput();
            $model = $this->getModel('OrganizationAppointment', 'Administrator', ['ignore_request' => true]);
            $model->endAppointment(
                $input->post->getInt('id', 0),
                $input->post->getCmd('end_reason', ''),
                $input->post->getString('ended_on', ''),
                $input->post->getString('end_note', '')
            );
            $this->respond(['ended' => true], Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_ENDED'));
        } catch (Throwable $e) {
            $this->respond(null, $e->getMessage(), true);
        }
    }

    public function delete(): void
    {
        if (!$this->checkPostToken()) {
            return;
        }

        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.delete', 'com_xdecaroorganizations')
            && !$user->authorise('core.admin', 'com_xdecaroorganizations')) {
            $this->respond(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            return;
        }

        try {
            $input = Factory::getApplication()->getInput();
            $model = $this->getModel('OrganizationAppointment', 'Administrator', ['ignore_request' => true]);
            $model->deleteAppointment($input->post->getInt('id', 0));
            $this->respond(['deleted' => true], Text::_('COM_XDECAROORGANIZATIONS_APPOINTMENT_DELETED'));
        } catch (Throwable $e) {
            $this->respond(null, $e->getMessage(), true);
        }
    }

    private function checkPostToken(): bool
    {
        if (Session::checkToken('post')) {
            return true;
        }

        $this->respond(null, Text::_('JINVALID_TOKEN'), true);
        return false;
    }

    private function personName(array $person): string
    {
        $display = trim((string) ($person['display_name'] ?? ''));
        if ($display !== '') {
            return $display;
        }

        $preferred = trim((string) ($person['preferred_name'] ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        return trim(trim((string) ($person['first_name'] ?? '')) . ' ' . trim((string) ($person['last_name'] ?? '')));
    }

    private function respond(mixed $data = null, string $message = '', bool $error = false): void
    {
        echo new JsonResponse($data, $message, $error, true);
        Factory::getApplication()->close();
    }
}
