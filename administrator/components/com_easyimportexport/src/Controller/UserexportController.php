<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class UserexportController extends BaseController
{
    public function export()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $cid = $app->getInput()->get('cid_users', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_USER_NO_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
            return;
        }

        $model = $this->getModel('Users', 'Administrator');
        $exportData = $model->getExportData(array_map('intval', $cid));

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
            return;
        }

        $this->sendJson($app, $exportData, 'joomla_users_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportAll()
    {
        if (!Session::checkToken('get')) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $groupId = $app->getInput()->getInt('filter_group', 0);

        $model = $this->getModel('Users', 'Administrator');
        $ids = $model->getAllUserIds($groupId);

        if (empty($ids)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_NO_MODULES_FOUND'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
            return;
        }

        $exportData = $model->getExportData($ids);

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
            return;
        }

        $suffix = $groupId > 0 ? '_group' . $groupId : '_all';
        $this->sendJson($app, $exportData, 'joomla_users_export' . $suffix . '_' . date('Y-m-d_His') . '.json');
    }

    protected function sendJson($app, array $data, string $filename): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $app->clearHeaders();
        $app->setHeader('Content-Type', 'application/json', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Content-Length', strlen($json), true);
        $app->setHeader('Cache-Control', 'no-cache, must-revalidate', true);
        $app->sendHeaders();
        echo $json;
        $app->close();
    }
}
