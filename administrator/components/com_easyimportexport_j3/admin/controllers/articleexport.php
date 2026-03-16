<?php

defined('_JEXEC') or die;

class EasyimportexportControllerArticleexport extends JControllerLegacy
{
    public function exportArticles()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $cid = $app->input->get('cid_articles', array(), 'array');

        if (empty($cid)) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_ARTICLE_NO_SELECTED'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

        JArrayHelper::toInteger($cid);

        $model      = $this->getModel('Articles');
        $exportData = $model->getExportArticles($cid);

        if ($exportData === false) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

        $this->sendJson($app, $exportData, 'joomla_articles_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportCategories()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app = JFactory::getApplication();
        $cid = $app->input->get('cid_categories', array(), 'array');

        if (empty($cid)) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_CAT_NO_SELECTED'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

        JArrayHelper::toInteger($cid);

        $model      = $this->getModel('Articles');
        $exportData = $model->getExportCategories($cid);

        if ($exportData === false) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

        $this->sendJson($app, $exportData, 'joomla_categories_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportAll()
    {
        JSession::checkToken('get') or jexit(JText::_('JINVALID_TOKEN'));

        $app   = JFactory::getApplication();
        $what  = $app->input->getString('what', 'articles');
        $model = $this->getModel('Articles');

        if ($what === 'categories') {
            $ids        = $model->getAllCategoryIds();
            $exportData = $model->getExportCategories($ids);
            $filename   = 'joomla_categories_export_all_' . date('Y-m-d_His') . '.json';
        } else {
            $ids        = $model->getAllArticleIds();
            $exportData = $model->getExportArticles($ids);
            $filename   = 'joomla_articles_export_all_' . date('Y-m-d_His') . '.json';
        }

        if ($exportData === false) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_NO_MODULES_FOUND'), 'warning');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

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
