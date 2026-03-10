<?php

namespace Joomla\Component\Easyimportexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class ArticleexportController extends BaseController
{
    public function exportArticles()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $cid = $app->getInput()->get('cid_articles', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_ARTICLE_NO_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        $model = $this->getModel('Articles', 'Administrator');
        $exportData = $model->getExportArticles(array_map('intval', $cid));

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        $this->sendJson($app, $exportData, 'joomla_articles_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportCategories()
    {
        if (!Session::checkToken()) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $cid = $app->getInput()->get('cid_categories', [], 'array');

        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_CAT_NO_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        $model = $this->getModel('Articles', 'Administrator');
        $exportData = $model->getExportCategories(array_map('intval', $cid));

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_EXPORT_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        $this->sendJson($app, $exportData, 'joomla_categories_export_' . date('Y-m-d_His') . '.json');
    }

    public function exportAll()
    {
        if (!Session::checkToken('get')) {
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false), Text::_('JINVALID_TOKEN'), 'error');
            return;
        }

        $app = Factory::getApplication();
        $what = $app->getInput()->getString('what', 'articles');
        $catId = $app->getInput()->getInt('filter_catid', 0);

        $model = $this->getModel('Articles', 'Administrator');

        if ($what === 'categories') {
            $ids = $model->getAllCategoryIds();
            $exportData = $model->getExportCategories($ids);
            $filename = 'joomla_categories_export_all_' . date('Y-m-d_His') . '.json';
        } else {
            $ids = $model->getAllArticleIds($catId);
            $exportData = $model->getExportArticles($ids);
            $suffix = $catId > 0 ? '_cat' . $catId : '_all';
            $filename = 'joomla_articles_export' . $suffix . '_' . date('Y-m-d_His') . '.json';
        }

        if ($exportData === false) {
            $app->enqueueMessage(Text::_('COM_EASYIMPORTEXPORT_NO_MODULES_FOUND'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles', false));
            return;
        }

        $this->sendJson($app, $exportData, $filename);
    }

    protected function sendJson($app, array $data, string $filename): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $app->clearHeaders();
        $app->setHeader('Content-Type', 'application/json', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"', true);
        $app->setHeader('Content-Length', strlen($json), true);
        $app->setHeader('Cache-Control', 'no-cache, must-revalidate', true);
        $app->sendHeaders();
        echo $json;
        $app->close();
    }
}
