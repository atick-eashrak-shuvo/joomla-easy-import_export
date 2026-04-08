<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class ArticleimportController extends BaseController
{
    public function import()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $file = $app->getInput()->files->get('import_file_articles', null, 'raw');
        $redirect = Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false);

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_NO_FILE'), 'error');
            $this->setRedirect($redirect);
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['json', 'zip'], true)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_FORMAT'), 'error');
            $this->setRedirect($redirect);
            return;
        }

        $overwrite = $app->getInput()->getInt('import_overwrite_articles', 0);
        $model = $this->getModel('Articles', 'Administrator');
        $mediaDir = '';
        $extractDir = '';

        if ($ext === 'zip') {
            $extracted = $this->extractZip($file['tmp_name']);
            if ($extracted === false) {
                $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
                $this->setRedirect($redirect);
                return;
            }
            $extractDir = $extracted['dir'];
            $data = $extracted['data'];
            $mediaDir = $extracted['media_dir'];
        } else {
            $data = json_decode(file_get_contents($file['tmp_name']), true);
        }

        if ($data === null || !isset($data['meta'])) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
            $this->cleanup($extractDir);
            $this->setRedirect($redirect);
            return;
        }

        $result = $model->importArticles($data, (bool) $overwrite, $mediaDir);

        $this->cleanup($extractDir);

        if ($result['success']) {
            $msg = Text::sprintf(
                'COM_EASYIMPORTEXPORT_ARTICLE_IMPORT_SUCCESS',
                $result['imported'], $result['skipped'], $result['updated'], $result['cats_created'] ?? 0
            );
            $app->enqueueMessage($msg, 'message');

            $mediaWritten = $result['media_written'] ?? 0;
            if ($mediaWritten > 0) {
                $app->enqueueMessage(Text::sprintf('COM_EASYIMPORTEXPORT_MEDIA_IMPORT_SUCCESS', $mediaWritten), 'message');
            }
        } else {
            $app->enqueueMessage(Text::sprintf('COM_EASYIMPORTEXPORT_IMPORT_FAILED', $result['error']), 'error');
        }

        foreach ($result['warnings'] ?? [] as $w) {
            $app->enqueueMessage($w, 'warning');
        }

        $this->setRedirect($redirect);
    }

    protected function extractZip(string $tmpPath): array|false
    {
        $zip = new \ZipArchive();
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

        return [
            'dir'       => $extractDir,
            'data'      => $data,
            'media_dir' => $mediaDir,
        ];
    }

    protected function cleanup(string $dir): void
    {
        if (empty($dir) || !is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
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
