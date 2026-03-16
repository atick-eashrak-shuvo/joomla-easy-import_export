<?php

defined('_JEXEC') or die;

jimport('joomla.application.component.model');

class EasyimportexportModelArticles extends JModelLegacy
{
    public function getAllArticleIds()
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__content'));

        $db->setQuery($query);

        return (array) $db->loadColumn();
    }

    public function getAllCategoryIds()
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
            ->where($db->quoteName('id') . ' > 1');

        $db->setQuery($query);

        return (array) $db->loadColumn();
    }

    public function getExportCategories(array $catIds)
    {
        if (empty($catIds)) {
            return false;
        }

        $db = JFactory::getDbo();
        JArrayHelper::toInteger($catIds);
        $idsList = implode(',', $catIds);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('id') . ' IN (' . $idsList . ')');

        $db->setQuery($query);
        $cats = (array) $db->loadAssocList();

        if (empty($cats)) {
            return false;
        }

        foreach ($cats as &$cat) {
            unset($cat['checked_out'], $cat['checked_out_time'], $cat['asset_id']);
        }

        return array(
            'meta'       => array(
                'format_version' => '1.0',
                'type'           => 'categories',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => JFactory::getApplication()->getCfg('sitename', ''),
                'site_url'       => JUri::root(),
                'item_count'     => count($cats),
            ),
            'categories' => $cats,
        );
    }

    public function getExportArticles(array $articleIds)
    {
        if (empty($articleIds)) {
            return false;
        }

        $db = JFactory::getDbo();
        JArrayHelper::toInteger($articleIds);
        $idsList = implode(',', $articleIds);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' IN (' . $idsList . ')');

        $db->setQuery($query);
        $articles = (array) $db->loadAssocList();

        if (empty($articles)) {
            return false;
        }

        $catIds = array();
        foreach ($articles as $article) {
            if (!empty($article['catid'])) {
                $catIds[] = (int) $article['catid'];
            }
        }
        $catIds = array_unique($catIds);

        $categories = array();

        if (!empty($catIds)) {
            JArrayHelper::toInteger($catIds);
            $idsList = implode(',', $catIds);

            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('id') . ' IN (' . $idsList . ')');

            $db->setQuery($query);
            $categories = (array) $db->loadAssocList();

            foreach ($categories as &$cat) {
                unset($cat['checked_out'], $cat['checked_out_time'], $cat['asset_id']);
            }
        }

        foreach ($articles as &$article) {
            unset($article['checked_out'], $article['checked_out_time'], $article['asset_id']);
        }

        return array(
            'meta'       => array(
                'format_version' => '1.0',
                'type'           => 'articles',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => JFactory::getApplication()->getCfg('sitename', ''),
                'site_url'       => JUri::root(),
                'article_count'  => count($articles),
                'category_count' => count($categories),
            ),
            'categories' => $categories,
            'articles'   => $articles,
        );
    }

    public function importArticles(array $data, $overwrite = false)
    {
        $result = array(
            'success'      => true,
            'imported'     => 0,
            'skipped'      => 0,
            'updated'      => 0,
            'cats_created' => 0,
            'error'        => '',
            'warnings'     => array(),
        );

        $db         = JFactory::getDbo();
        $categories = isset($data['categories']) ? $data['categories'] : array();
        $articles   = isset($data['articles']) ? $data['articles'] : array();
        $dataType   = isset($data['meta']['type']) ? $data['meta']['type'] : '';

        if ($dataType === 'categories' && !empty($categories) && empty($articles)) {
            return $this->importCategoriesOnly($db, $categories, $overwrite);
        }

        if (empty($articles)) {
            $result['success'] = false;
            $result['error']   = 'No articles found in import file.';
            return $result;
        }

        $catIdMap = array();
        foreach ($categories as $cat) {
            $originalCatId = isset($cat['id']) ? (int) $cat['id'] : 0;
            $newCatId      = $this->ensureCategory($db, $cat, $overwrite);
            if ($newCatId) {
                $catIdMap[$originalCatId] = $newCatId;
                $result['cats_created']++;
            }
        }

        foreach ($articles as $article) {
            try {
                $originalId = isset($article['id']) ? (int) $article['id'] : 0;
                unset($article['id'], $article['checked_out'], $article['checked_out_time'], $article['asset_id']);

                if (isset($article['catid']) && isset($catIdMap[(int) $article['catid']])) {
                    $article['catid'] = $catIdMap[(int) $article['catid']];
                }

                $existing = null;
                if ($overwrite && $originalId > 0) {
                    $existing = $this->findExistingArticle($db, $originalId, isset($article['alias']) ? $article['alias'] : '');
                }

                if ($existing) {
                    $article['id'] = (int) $existing->id;
                    $this->updateArticle($db, $article);
                    $result['updated']++;
                } else {
                    $article['asset_id'] = 0;
                    $newId = $this->insertArticle($db, $article);
                    if ($newId) {
                        $result['imported']++;
                    } else {
                        $result['skipped']++;
                    }
                }
            } catch (Exception $e) {
                $title = isset($article['title']) ? $article['title'] : 'Unknown';
                $result['warnings'][] = sprintf('Error importing article "%s": %s', $title, $e->getMessage());
                $result['skipped']++;
            }
        }

        return $result;
    }

    protected function importCategoriesOnly($db, array $categories, $overwrite)
    {
        $result = array(
            'success'      => true,
            'imported'     => 0,
            'skipped'      => 0,
            'updated'      => 0,
            'cats_created' => 0,
            'error'        => '',
            'warnings'     => array(),
        );

        usort($categories, array($this, 'sortByLevel'));

        foreach ($categories as $cat) {
            try {
                $id = $this->ensureCategory($db, $cat, $overwrite);
                if ($id) {
                    $result['cats_created']++;
                }
            } catch (Exception $e) {
                $title = isset($cat['title']) ? $cat['title'] : 'Unknown';
                $result['warnings'][] = sprintf('Error importing category "%s": %s', $title, $e->getMessage());
                $result['skipped']++;
            }
        }

        $result['imported'] = $result['cats_created'];
        return $result;
    }

    public function sortByLevel($a, $b)
    {
        $la = isset($a['level']) ? (int) $a['level'] : 1;
        $lb = isset($b['level']) ? (int) $b['level'] : 1;
        return $la - $lb;
    }

    protected function ensureCategory($db, array $cat, $overwrite)
    {
        $originalId = isset($cat['id']) ? (int) $cat['id'] : 0;
        unset($cat['id'], $cat['checked_out'], $cat['checked_out_time'], $cat['asset_id']);

        $alias     = isset($cat['alias']) ? $cat['alias'] : '';
        $extension = isset($cat['extension']) ? $cat['extension'] : 'com_content';

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($alias))
            ->where($db->quoteName('extension') . ' = ' . $db->quote($extension));
        $db->setQuery($query, 0, 1);
        $existingId = $db->loadResult();

        if ($existingId) {
            if ($overwrite) {
                $cat['id'] = (int) $existingId;
                $this->updateCategory($db, $cat);
            }
            return (int) $existingId;
        }

        $cat['asset_id'] = 0;
        return $this->insertCategory($db, $cat);
    }

    protected function findExistingArticle($db, $id, $alias)
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = ' . (int) $id);
        $db->setQuery($query);
        $obj = $db->loadObject();
        if ($obj) {
            return $obj;
        }

        if (!empty($alias)) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));
            $db->setQuery($query, 0, 1);
            return $db->loadObject();
        }

        return null;
    }

    protected function insertArticle($db, array $data)
    {
        $cols = array(
            'title', 'alias', 'introtext', 'fulltext', 'state', 'catid',
            'created', 'created_by', 'created_by_alias', 'modified', 'modified_by',
            'publish_up', 'publish_down', 'images', 'urls', 'attribs', 'version',
            'ordering', 'metakey', 'metadesc', 'access', 'hits', 'metadata',
            'featured', 'language', 'note', 'asset_id',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            $obj->$col = isset($data[$col]) ? $data[$col] : null;
        }
        $obj->checked_out      = 0;
        $obj->checked_out_time = $db->getNullDate();

        if ($db->insertObject('#__content', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateArticle($db, array $data)
    {
        $cols = array(
            'id', 'title', 'alias', 'introtext', 'fulltext', 'state', 'catid',
            'created', 'created_by', 'created_by_alias', 'modified', 'modified_by',
            'publish_up', 'publish_down', 'images', 'urls', 'attribs', 'version',
            'ordering', 'metakey', 'metadesc', 'access', 'hits', 'metadata',
            'featured', 'language', 'note',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            if (isset($data[$col])) {
                $obj->$col = $data[$col];
            }
        }

        return $db->updateObject('#__content', $obj, 'id');
    }

    protected function insertCategory($db, array $data)
    {
        $cols = array(
            'parent_id', 'lft', 'rgt', 'level', 'path', 'extension',
            'title', 'alias', 'note', 'description', 'published', 'access',
            'params', 'metadesc', 'metakey', 'metadata', 'created_user_id',
            'created_time', 'modified_user_id', 'modified_time', 'hits',
            'language', 'version', 'asset_id',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            $obj->$col = isset($data[$col]) ? $data[$col] : null;
        }
        $obj->checked_out      = 0;
        $obj->checked_out_time = $db->getNullDate();

        if ($db->insertObject('#__categories', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateCategory($db, array $data)
    {
        $cols = array(
            'id', 'parent_id', 'level', 'path', 'extension', 'title', 'alias',
            'note', 'description', 'published', 'access', 'params', 'metadesc',
            'metakey', 'metadata', 'language',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            if (isset($data[$col])) {
                $obj->$col = $data[$col];
            }
        }

        return $db->updateObject('#__categories', $obj, 'id');
    }

    public function getCategories($search = '')
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select(array(
                $db->quoteName('c.id'),
                $db->quoteName('c.title'),
                $db->quoteName('c.alias'),
                $db->quoteName('c.parent_id'),
                $db->quoteName('c.level'),
                $db->quoteName('c.published'),
                $db->quoteName('c.language'),
                $db->quoteName('c.access'),
                $db->quoteName('c.extension'),
                $db->quoteName('ag.title', 'access_level'),
            ))
            ->from($db->quoteName('#__categories', 'c'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('c.access'))
            ->where($db->quoteName('c.extension') . ' = ' . $db->quote('com_content'))
            ->where($db->quoteName('c.id') . ' > 1')
            ->order($db->quoteName('c.lft') . ' ASC');

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where($db->quoteName('c.title') . ' LIKE ' . $db->quote($wild));
        }

        $db->setQuery($query);

        return (array) $db->loadObjectList();
    }

    public function getArticles($catId = 0, $search = '', $state = -3)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select(array(
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.state'),
                $db->quoteName('a.catid'),
                $db->quoteName('a.language'),
                $db->quoteName('a.access'),
                $db->quoteName('a.created'),
                $db->quoteName('a.featured'),
                $db->quoteName('c.title', 'category_title'),
                $db->quoteName('ag.title', 'access_level'),
            ))
            ->from($db->quoteName('#__content', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'))
            ->order($db->quoteName('a.created') . ' DESC');

        if ((int) $catId > 0) {
            $query->where($db->quoteName('a.catid') . ' = ' . (int) $catId);
        }

        if ((int) $state > -3) {
            $query->where($db->quoteName('a.state') . ' = ' . (int) $state);
        }

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('a.title') . ' LIKE ' . $db->quote($wild)
                . ' OR ' . $db->quoteName('a.alias') . ' LIKE ' . $db->quote($wild) . ')'
            );
        }

        $db->setQuery($query);

        return (array) $db->loadObjectList();
    }

    public function getCategoryList()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select(array($db->quoteName('id'), $db->quoteName('title'), $db->quoteName('level')))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
            ->where($db->quoteName('id') . ' > 1')
            ->order($db->quoteName('lft') . ' ASC');

        $db->setQuery($query);

        return (array) $db->loadObjectList();
    }
}

