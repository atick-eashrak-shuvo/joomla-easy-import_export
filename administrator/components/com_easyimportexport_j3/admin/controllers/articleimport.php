<?php

defined('_JEXEC') or die;

class EasyimportexportControllerArticleimport extends JControllerLegacy
{
    public function import()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app  = JFactory::getApplication();
        $file = $app->input->files->get('import_file_articles', null, 'raw');

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_IMPORT_NO_FILE'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

        $ext = strtolower(JFile::getExt($file['name']));

        if ($ext !== 'json') {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_FORMAT'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

        $content = file_get_contents($file['tmp_name']);
        $data    = json_decode($content, true);

        if ($data === null || !isset($data['meta'])) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
            $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));

            return;
        }

        $overwrite = $app->input->getInt('import_overwrite_articles', 0);

        $model  = $this->getModel('Articles');
        $result = $model->importArticles($data, (bool) $overwrite);

        if ($result['success']) {
            $msg = JText::sprintf('COM_EASYIMPORTEXPORT_IMPORT_SUCCESS', $result['imported'], $result['skipped'], $result['updated']);
            $app->enqueueMessage($msg, 'message');

            $mediaWritten = isset($result['media_written']) ? (int) $result['media_written'] : 0;
            if ($mediaWritten > 0) {
                $app->enqueueMessage(JText::sprintf('COM_EASYIMPORTEXPORT_MEDIA_IMPORT_SUCCESS', $mediaWritten), 'message');
            }
        } else {
            $app->enqueueMessage(JText::sprintf('COM_EASYIMPORTEXPORT_IMPORT_FAILED', $result['error']), 'error');
        }

        if (!empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                $app->enqueueMessage($warning, 'warning');
            }
        }

        $this->setRedirect(JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
    }
}
