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

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_NO_FILE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json') {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_FORMAT'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        $data = json_decode(file_get_contents($file['tmp_name']), true);

        if ($data === null || !isset($data['meta'])) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        $overwrite = $app->getInput()->getInt('import_overwrite_articles', 0);
        $model = $this->getModel('Articles', 'Administrator');
        $result = $model->importArticles($data, (bool) $overwrite);

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

        $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
    }
}
