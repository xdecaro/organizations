<?php

namespace xdecaro\Component\Organizations\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Throwable;

final class AffiliationController extends BaseController
{
    protected $option = 'com_xdecaroorganizations';

    public function searchTargets(): void
    {
        if (!Session::checkToken('post')) {
            $this->respond(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app = Factory::getApplication();
        $input = $app->getInput();
        $user = $app->getIdentity();

        if (!$user->authorise('core.edit', 'com_xdecaroorganizations')
            && !$user->authorise('core.create', 'com_xdecaroorganizations')
            && !$user->authorise('core.admin', 'com_xdecaroorganizations')) {
            $this->respond(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            return;
        }

        $organizationId = $input->post->getInt('organization_id', 0);
        $search = trim($input->post->getString('q', ''));
        $relationType = $input->post->getCmd('relation_type', 'sports_affiliation');

        if ($organizationId < 1) {
            $this->respond(null, Text::_('COM_XDECAROORGANIZATIONS_AFFILIATIONS_SAVE_FIRST'), true);
            return;
        }

        if (strlen($search) < 2) {
            $this->respond(['items' => []]);
            return;
        }

        try {
            $model = $this->getModel('Organization', 'Administrator', ['ignore_request' => true]);
            $items = $model->searchAffiliationTargets(
                $organizationId,
                $search,
                12,
                $relationType === 'sports_affiliation'
            );

            $payload = array_map(static function ($item): array {
                $type = strtolower(trim((string) ($item->type ?? 'organization')));

                return [
                    'id' => (int) ($item->id ?? 0),
                    'name' => (string) ($item->name ?? ''),
                    'code' => (string) ($item->code ?? ''),
                    'type' => $type,
                    'type_label' => Text::_('COM_XDECAROORGANIZATIONS_TYPE_' . strtoupper($type)),
                    'structure_level' => (string) ($item->structure_level ?? ''),
                ];
            }, $items);

            $this->respond(['items' => $payload]);
        } catch (Throwable $e) {
            $this->respond(null, $e->getMessage(), true);
        }
    }

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
            $model = $this->getModel('OrganizationAffiliation', 'Administrator', ['ignore_request' => true]);
            $affiliationId = $model->saveAffiliation([
                'id' => $id,
                'organization_id' => $input->post->getInt('organization_id', 0),
                'target_organization_id' => $input->post->getInt('target_organization_id', 0),
                'relation_type' => $input->post->getCmd('relation_type', 'sports_affiliation'),
                'relation_code' => $input->post->getString('relation_code', ''),
                'starts_on' => $input->post->getString('starts_on', ''),
                'ends_on' => $input->post->getString('ends_on', ''),
                'status' => $input->post->getCmd('status', 'active'),
                'notes' => $input->post->getString('notes', ''),
                'state' => $input->post->getInt('state', 1),
            ]);
            $this->respond(['id' => $affiliationId], Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_SAVED'));
        } catch (Throwable $e) {
            $this->respond(null, $e->getMessage(), true);
        }
    }

    public function delete(): void
    {
        if (!Session::checkToken('post')) {
            $this->respond(null, Text::_('JINVALID_TOKEN'), true);
            return;
        }

        $app = Factory::getApplication();
        $user = $app->getIdentity();

        if (!$user->authorise('core.delete', 'com_xdecaroorganizations')
            && !$user->authorise('core.admin', 'com_xdecaroorganizations')) {
            $this->respond(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            return;
        }

        $id = $app->getInput()->post->getInt('id', 0);

        try {
            $model = $this->getModel('OrganizationAffiliation', 'Administrator', ['ignore_request' => true]);
            $model->deleteAffiliation($id);
            $this->respond(['id' => $id], Text::_('COM_XDECAROORGANIZATIONS_AFFILIATION_DELETED'));
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
