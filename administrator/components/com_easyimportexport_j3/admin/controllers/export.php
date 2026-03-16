<?php

defined('_JEXEC') or die;

class EasyimportexportControllerExport extends JControllerLegacy
{
    public function export()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $cid = $app->input->get('cid', array(), 'array');

        if (empty($cid)) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_NO_MODULES_SELECTED'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules', false));

            return;
        }

        JArrayHelper::toInteger($cid);

        $model      = $this->getModel('Modules');
        $exportData = $model->getExportData($cid);

        if ($exportData === false) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules', false));

            return;
        }

        $this->sendJson($app, $exportData, 'joomla_modules_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportAll()
    {
        JSession::checkToken('get') or jexit(JText::_('JINVALID_TOKEN'));

        $app      = JFactory::getApplication();
        $clientId = $app->input->getInt('client_id', -1);

        $model      = $this->getModel('Modules');
        $allModules = $model->getAllModuleIds($clientId);

        if (empty($allModules)) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_NO_MODULES_FOUND'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules', false));

            return;
        }

        $exportData = $model->getExportData($allModules);

        if ($exportData === false) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules', false));

            return;
        }

        $suffix   = $clientId === 0 ? '_site' : ($clientId === 1 ? '_admin' : '_all');
        $filename = 'joomla_modules_export' . $suffix . '_' . date('Y-m-d_His') . '.json';

        $this->sendJson($app, $exportData, $filename);
    }

    protected function sendJson($app, array $data, $filename)
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
