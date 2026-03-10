<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class UserimportController extends BaseController
{
    public function import()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $file = $app->getInput()->files->get('import_file_users', null, 'raw');

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_NO_FILE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
            return;
        }

        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json') {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_FORMAT'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
            return;
        }

        $data = json_decode(file_get_contents($file['tmp_name']), true);

        if ($data === null || !isset($data['meta']) || !isset($data['users'])) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
            return;
        }

        $overwrite = $app->getInput()->getInt('import_overwrite_users', 0);
        $model = $this->getModel('Users', 'Administrator');
        $result = $model->importUsers($data, (bool) $overwrite);

        if ($result['success']) {
            $app->enqueueMessage(
                Text::sprintf('COM_EASYIMPORTEXPORT_IMPORT_SUCCESS', $result['imported'], $result['skipped'], $result['updated']),
                'message'
            );
        } else {
            $app->enqueueMessage(Text::sprintf('COM_EASYIMPORTEXPORT_IMPORT_FAILED', $result['error']), 'error');
        }

        foreach ($result['warnings'] ?? [] as $w) {
            $app->enqueueMessage($w, 'warning');
        }

        $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=users', false));
    }
}
