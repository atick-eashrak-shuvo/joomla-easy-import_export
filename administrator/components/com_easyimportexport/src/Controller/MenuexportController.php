<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class MenuexportController extends BaseController
{
    public function export()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $cid = $app->getInput()->get('cid', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_MENU_NO_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false));
            return;
        }

        $cid = array_map('intval', $cid);
        $model = $this->getModel('Menus', 'Administrator');
        $exportData = $model->getExportData($cid);

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false));
            return;
        }

        $this->sendJson($app, $exportData, 'joomla_menus_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportAll()
    {
        if (!Session::checkToken('get')) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $menutype = $app->getInput()->getString('menutype', '');

        $model = $this->getModel('Menus', 'Administrator');

        if (!empty($menutype)) {
            $exportData = $model->getExportDataByMenutype($menutype);
            $suffix = '_' . $menutype;
        } else {
            $ids = $model->getAllSiteMenuItemIds();
            $exportData = $model->getExportData($ids);
            $suffix = '_all';
        }

        if ($exportData === false || empty($exportData['menu_items'])) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_NO_MODULES_FOUND'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false));
            return;
        }

        $this->sendJson($app, $exportData, 'joomla_menus_export' . $suffix . '_' . date('Y-m-d_His') . '.json');
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
