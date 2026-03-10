<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class ExportController extends BaseController
{
    public function export()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $input = $app->getInput();
        $cid = $input->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_NO_MODULES_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
            return;
        }

        $cid = array_map('intval', $cid);

        $model = $this->getModel('Modules', 'Administrator');
        $exportData = $model->getExportData($cid);

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
            return;
        }

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'joomla_modules_export_' . date('Y-m-d_His') . '.json';

        $app->clearHeaders();
        $app->setHeader('Content-Type', 'application/json', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Content-Length', strlen($json), true);
        $app->setHeader('Cache-Control', 'no-cache, must-revalidate', true);
        $app->setHeader('Pragma', 'no-cache', true);
        $app->sendHeaders();

        echo $json;

        $app->close();
    }

    public function exportAll()
    {
        if (!Session::checkToken('get')) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $input = $app->getInput();
        $clientId = $input->getInt('client_id', -1);

        $model = $this->getModel('Modules', 'Administrator');
        $allModules = $model->getAllModuleIds($clientId);

        if (empty($allModules)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_NO_MODULES_FOUND'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
            return;
        }

        $exportData = $model->getExportData($allModules);

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
            return;
        }

        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $suffix = $clientId === 0 ? '_site' : ($clientId === 1 ? '_admin' : '_all');
        $filename = 'joomla_modules_export' . $suffix . '_' . date('Y-m-d_His') . '.json';

        $app->clearHeaders();
        $app->setHeader('Content-Type', 'application/json', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Content-Length', strlen($json), true);
        $app->setHeader('Cache-Control', 'no-cache, must-revalidate', true);
        $app->setHeader('Pragma', 'no-cache', true);
        $app->sendHeaders();

        echo $json;

        $app->close();
    }
}
