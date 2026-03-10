<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class MenuimportController extends BaseController
{
    public function import()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $file = $app->getInput()->files->get('import_file_menus', null, 'raw');

        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_NO_FILE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false));
            return;
        }

        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'json') {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_FORMAT'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false));
            return;
        }

        $data = json_decode(file_get_contents($file['tmp_name']), true);

        if ($data === null || !isset($data['meta']) || !isset($data['menu_items'])) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_IMPORT_INVALID_DATA'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false));
            return;
        }

        $overwrite = $app->getInput()->getInt('import_overwrite_menus', 0);
        $model = $this->getModel('Menus', 'Administrator');
        $result = $model->importMenus($data, (bool) $overwrite);

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

        $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus', false));
    }
}
