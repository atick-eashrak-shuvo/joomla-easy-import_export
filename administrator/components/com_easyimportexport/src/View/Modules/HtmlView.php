<?php

namespace Joomla\Component\Easyimportexport\Administrator\View\Modules;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
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

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $this->activeTab = $input->getString('tab', 'modules');

        // --- Modules ---
        $modulesModel = $this->getModel();
        $this->modules = $modulesModel->getModules(
            $input->getInt('client_id', -1),
            $input->getString('search', ''),
            $input->getString('filter_position', ''),
            $input->getInt('filter_state', -3)
        );
        $this->positions = $modulesModel->getPositions();

        // --- Menus ---
        $menusModel = $this->getModel('Menus');
        $this->menuTypes = $menusModel->getMenuTypes();
        $this->menuItems = $menusModel->getMenuItems(
            $input->getString('filter_menutype', ''),
            $input->getString('menu_search', ''),
            $input->getInt('menu_state', -3)
        );

        // --- Articles ---
        $articlesModel = $this->getModel('Articles');
        $this->categories = $articlesModel->getCategories($input->getString('cat_search', ''));
        $this->articles = $articlesModel->getArticles(
            $input->getInt('filter_catid', 0),
            $input->getString('article_search', ''),
            $input->getInt('article_state', -3)
        );
        $this->categoryList = $articlesModel->getCategoryList();

        // --- Users ---
        $usersModel = $this->getModel('Users');
        $this->users = $usersModel->getUsers(
            $input->getString('user_search', ''),
            $input->getInt('filter_group', 0),
            $input->getInt('filter_block', -1)
        );
        $this->userGroups = $usersModel->getUserGroups();

        $this->activeFilters = [
            'client_id'      => $input->getInt('client_id', -1),
            'search'         => $input->getString('search', ''),
            'position'       => $input->getString('filter_position', ''),
            'state'          => $input->getInt('filter_state', -3),
            'filter_menutype' => $input->getString('filter_menutype', ''),
            'menu_search'    => $input->getString('menu_search', ''),
            'menu_state'     => $input->getInt('menu_state', -3),
            'cat_search'     => $input->getString('cat_search', ''),
            'filter_catid'   => $input->getInt('filter_catid', 0),
            'article_search' => $input->getString('article_search', ''),
            'article_state'  => $input->getInt('article_state', -3),
            'user_search'    => $input->getString('user_search', ''),
            'filter_group'   => $input->getInt('filter_group', 0),
            'filter_block'   => $input->getInt('filter_block', -1),
        ];

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_EASYIMPORTEXPORT'), 'module');
        ToolbarHelper::preferences('com_easyimportexport');
    }
}
