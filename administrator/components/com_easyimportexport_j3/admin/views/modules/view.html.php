<?php

defined('_JEXEC') or die;

class EasyimportexportViewModules extends JViewLegacy
{
    protected $modules;
    protected $positions;
    protected $menuTypes;
    protected $menuItems;
    protected $categories;
    protected $articles;
    protected $categoryList;
    protected $users;
    protected $userGroups;
    protected $activeFilters;
    protected $activeTab;

    public function display($tpl = null)
    {
        $app   = JFactory::getApplication();
        $input = $app->input;

        $this->activeTab = $input->getString('tab', 'modules');

        $modulesModel        = $this->getModel('Modules');
        $this->modules       = $modulesModel->getModules(
            $input->getInt('client_id', -1),
            $input->getString('search', ''),
            $input->getString('filter_position', ''),
            $input->getInt('filter_state', -3)
        );
        $this->positions = $modulesModel->getPositions();

        $menusModel       = $this->getModel('Menus');
        $this->menuTypes  = $menusModel->getMenuTypes();
        $this->menuItems  = $menusModel->getMenuItems(
            $input->getString('filter_menutype', ''),
            $input->getString('menu_search', ''),
            $input->getInt('menu_state', -3)
        );

        $articlesModel      = $this->getModel('Articles');
        $this->categories   = $articlesModel->getCategories($input->getString('cat_search', ''));
        $this->articles     = $articlesModel->getArticles(
            $input->getInt('filter_catid', 0),
            $input->getString('article_search', ''),
            $input->getInt('article_state', -3)
        );
        $this->categoryList = $articlesModel->getCategoryList();

        $usersModel       = $this->getModel('Users');
        $this->users      = $usersModel->getUsers(
            $input->getString('user_search', ''),
            $input->getInt('filter_group', 0),
            $input->getInt('filter_block', -1)
        );
        $this->userGroups = $usersModel->getUserGroups();

        $this->activeFilters = array(
            'client_id'       => $input->getInt('client_id', -1),
            'search'          => $input->getString('search', ''),
            'position'        => $input->getString('filter_position', ''),
            'state'           => $input->getInt('filter_state', -3),
            'filter_menutype' => $input->getString('filter_menutype', ''),
            'menu_search'     => $input->getString('menu_search', ''),
            'menu_state'      => $input->getInt('menu_state', -3),
            'cat_search'      => $input->getString('cat_search', ''),
            'filter_catid'    => $input->getInt('filter_catid', 0),
            'article_search'  => $input->getString('article_search', ''),
            'article_state'   => $input->getInt('article_state', -3),
            'user_search'     => $input->getString('user_search', ''),
            'filter_group'    => $input->getInt('filter_group', 0),
            'filter_block'    => $input->getInt('filter_block', -1),
        );

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar()
    {
        JToolbarHelper::title(JText::_('COM_EASYIMPORTEXPORT'), 'module');
        JToolbarHelper::preferences('com_easyimportexport');
    }
}
