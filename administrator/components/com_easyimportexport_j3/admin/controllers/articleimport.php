<?php

defined('_JEXEC') or die;

class EasyimportexportControllerArticleimport extends JControllerLegacy
{
    public function import()
    {
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        $app      = JFactory::getApplication();
        $file     = $app->input->files->get('import_file_articles', null, 'raw');
        $redirect = JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false);

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_IMPORT_NO_FILE'), 'error');
            $this->setRedirect($redirect);

            return;
        }

        $ext = strtolower(JFile::getExt($file['name']));

        if ($ext !== 'json' && $ext !== 'zip') {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_FORMAT'), 'error');
            $this->setRedirect($redirect);

            return;
        }

        $overwrite  = $app->input->getInt('import_overwrite_articles', 0);
        $model      = $this->getModel('Articles');
        $mediaDir   = '';
        $extractDir = '';

        if ($ext === 'zip') {
            $extracted = $this->extractZip($file['tmp_name']);
            if ($extracted === false) {
                $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
                $this->setRedirect($redirect);

                return;
            }
            $extractDir = $extracted['dir'];
            $data       = $extracted['data'];
            $mediaDir   = $extracted['media_dir'];
        } else {
            $content = file_get_contents($file['tmp_name']);
            $data    = json_decode($content, true);
        }

        if ($data === null || !isset($data['meta'])) {
            $app->enqueueMessage(JText::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
            $this->cleanup($extractDir);
            $this->setRedirect($redirect);

            return;
        }

        $result = $model->importArticles($data, (bool) $overwrite, $mediaDir);

        $this->cleanup($extractDir);

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

        $this->setRedirect($redirect);
    }

    protected function extractZip($tmpPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            return false;
        }

        $extractDir = sys_get_temp_dir() . '/eie_import_' . uniqid();
        if (!mkdir($extractDir, 0755, true)) {
            $zip->close();

            return false;
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $jsonPath = $extractDir . '/data.json';
        if (!is_file($jsonPath)) {
            $this->cleanup($extractDir);

            return false;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if ($data === null) {
            $this->cleanup($extractDir);

            return false;
        }

        $mediaDir = $extractDir . '/media';
        if (!is_dir($mediaDir)) {
            $mediaDir = '';
        }

        return array(
            'dir'       => $extractDir,
            'data'      => $data,
            'media_dir' => $mediaDir,
        );
    }

    protected function cleanup($dir)
    {
        if (empty($dir) || !is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }
}
