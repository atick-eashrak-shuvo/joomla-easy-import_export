<?php

defined('_JEXEC') or die;

class EasyimportexportControllerUserexport extends JControllerLegacy
{
    public function export()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $cid = $app->input->get('cid_users', array(), 'array');

        if (empty($cid)) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_USER_NO_SELECTED'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));

            return;
        }

        JArrayHelper::toInteger($cid);

        $model      = $this->getModel('Users');
        $exportData = $model->getExportData($cid);

        if ($exportData === false) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));

            return;
        }

        $this->sendJson($app, $exportData, 'joomla_users_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportAll()
    {
        JSession::checkToken('get') or jexit(JText::_('JINVALID_TOKEN'));

        $app   = JFactory::getApplication();
        $model = $this->getModel('Users');
        $ids   = $model->getAllUserIds();

        if (empty($ids)) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_NO_MODULES_FOUND'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));

            return;
        }

        $exportData = $model->getExportData($ids);

        if ($exportData === false) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));

            return;
        }

        $this->sendJson($app, $exportData, 'joomla_users_export_all_' . date('Y-m-d_His') . '.json');
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
