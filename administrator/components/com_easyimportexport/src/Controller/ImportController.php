<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class ImportController extends BaseController
{
    public function import()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $input = $app->getInput();

        $file = $input->files->get('import_file', null, 'raw');

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_NO_FILE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_FORMAT'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
            return;
        }

        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if ($data === null || !isset($data['meta']) || !isset($data['modules'])) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
            return;
        }

        $overwrite = $input->getInt('import_overwrite', 0);

        $model = $this->getModel('Modules', 'Administrator');
        $result = $model->importModules($data, (bool) $overwrite);

        if ($result['success']) {
            $msg = Text::sprintf('COM_EASYIMPORTEXPORT_IMPORT_SUCCESS', $result['imported'], $result['skipped'], $result['updated']);
            $app->enqueueMessage($msg, 'message');
        } else {
            $app->enqueueMessage(Text::sprintf('COM_EASYIMPORTEXPORT_IMPORT_FAILED', $result['error']), 'error');
        }

        if (!empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                $app->enqueueMessage($warning, 'warning');
            }
        }

        $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules', false));
    }
}
