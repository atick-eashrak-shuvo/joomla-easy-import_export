<?php

defined('_JEXEC') or die;

class EasyimportexportController extends JControllerLegacy
{
    protected $default_view = 'modules';

    public function display($cachable = false, $urlparams = array())
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
