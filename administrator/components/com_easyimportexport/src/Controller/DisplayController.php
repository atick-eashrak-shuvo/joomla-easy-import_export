<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

class DisplayController extends BaseController
{
    protected $default_view = 'modules';

    public function display($cachable = false, $urlparams = []): static
    {
        $view = $this->getView('Modules', 'html');

        $view->setModel($this->getModel('Modules'), true);
        $view->setModel($this->getModel('Menus'));
        $view->setModel($this->getModel('Articles'));
        $view->setModel($this->getModel('Users'));

        $view->display();

        return $this;
    }
}
