<?php

namespace Joomla\Component\Easyimportexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

class ArticlesModel extends BaseDatabaseModel
{
    public function getCategories(string $search = ''): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
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
            ])
            ->from($db->quoteName('#__categories', 'c'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('c.access'))
            ->where($db->quoteName('c.extension') . ' = ' . $db->quote('com_content'))
            ->where($db->quoteName('c.id') . ' > 1')
            ->order($db->quoteName('c.lft') . ' ASC');

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where($db->quoteName('c.title') . ' LIKE :s1')
                ->bind(':s1', $wild);
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getArticles(int $catId = 0, string $search = '', int $state = -3): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
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
            ])
            ->from($db->quoteName('#__content', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c'), $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'))
            ->order($db->quoteName('a.created') . ' DESC');

        if ($catId > 0) {
            $query->where($db->quoteName('a.catid') . ' = :catid')
                ->bind(':catid', $catId, \Joomla\Database\ParameterType::INTEGER);
        }

        if ($state > -3) {
            $query->where($db->quoteName('a.state') . ' = :state')
                ->bind(':state', $state, \Joomla\Database\ParameterType::INTEGER);
        }

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('a.title') . ' LIKE :s1'
                . ' OR ' . $db->quoteName('a.alias') . ' LIKE :s2)'
            )
            ->bind(':s1', $wild)->bind(':s2', $wild);
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getCategoryList(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('title'), $db->quoteName('level')])
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
            ->where($db->quoteName('id') . ' > 1')
            ->order($db->quoteName('lft') . ' ASC');
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    // --- Export ---

    public function getExportCategories(array $catIds): array|false
    {
        if (empty($catIds)) {
            return false;
        }

        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__categories'))
            ->whereIn($db->quoteName('id'), $catIds);
        $db->setQuery($query);
        $cats = $db->loadAssocList();

        if (empty($cats)) {
            return false;
        }

        foreach ($cats as &$cat) {
            unset($cat['checked_out'], $cat['checked_out_time'], $cat['asset_id']);
        }

        return [
            'meta' => [
                'format_version' => '1.0',
                'type'           => 'categories',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => Factory::getApplication()->get('sitename', ''),
                'site_url'       => (string) \Joomla\CMS\Uri\Uri::root(),
                'item_count'     => count($cats),
            ],
            'categories' => $cats,
        ];
    }

    public function getExportArticles(array $articleIds): array|false
    {
        if (empty($articleIds)) {
            return false;
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__content'))
            ->whereIn($db->quoteName('id'), $articleIds);
        $db->setQuery($query);
        $articles = $db->loadAssocList();

        if (empty($articles)) {
            return false;
        }

        $catIds = array_unique(array_filter(array_column($articles, 'catid')));
        $categories = [];
        if (!empty($catIds)) {
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__categories'))
                ->whereIn($db->quoteName('id'), $catIds);
            $db->setQuery($query);
            $categories = $db->loadAssocList();

            foreach ($categories as &$cat) {
                unset($cat['checked_out'], $cat['checked_out_time'], $cat['asset_id']);
            }
        }

        foreach ($articles as &$article) {
            unset($article['checked_out'], $article['checked_out_time'], $article['asset_id']);
        }

        return [
            'meta' => [
                'format_version' => '1.0',
                'type'           => 'articles',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => Factory::getApplication()->get('sitename', ''),
                'site_url'       => (string) \Joomla\CMS\Uri\Uri::root(),
                'article_count'  => count($articles),
                'category_count' => count($categories),
            ],
            'categories' => $categories,
            'articles'   => $articles,
        ];
    }

    public function getAllArticleIds(int $catId = 0): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__content'));

        if ($catId > 0) {
            $query->where($db->quoteName('catid') . ' = :catid')
                ->bind(':catid', $catId, \Joomla\Database\ParameterType::INTEGER);
        }

        $db->setQuery($query);
        return $db->loadColumn();
    }

    public function getAllCategoryIds(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('com_content'))
            ->where($db->quoteName('id') . ' > 1');
        $db->setQuery($query);
        return $db->loadColumn();
    }

    // --- Import ---

    public function importArticles(array $data, bool $overwrite = false): array
    {
        $result = [
            'success' => true, 'imported' => 0, 'skipped' => 0,
            'updated' => 0, 'cats_created' => 0, 'error' => '', 'warnings' => [],
        ];

        $db = $this->getDatabase();

        $categories = $data['categories'] ?? [];
        $articles = $data['articles'] ?? [];
        $dataType = $data['meta']['type'] ?? '';

        if ($dataType === 'categories' && !empty($categories) && empty($articles)) {
            return $this->importCategoriesOnly($db, $categories, $overwrite);
        }

        if (empty($articles)) {
            $result['success'] = false;
            $result['error'] = 'No articles found in import file.';
            return $result;
        }

        $catIdMap = [];
        foreach ($categories as $cat) {
            $originalCatId = (int) ($cat['id'] ?? 0);
            $newCatId = $this->ensureCategory($db, $cat, $overwrite);
            if ($newCatId) {
                $catIdMap[$originalCatId] = $newCatId;
                $result['cats_created']++;
            }
        }

        foreach ($articles as $article) {
            try {
                $originalId = (int) ($article['id'] ?? 0);
                unset($article['id'], $article['checked_out'], $article['checked_out_time'], $article['asset_id']);

                if (isset($article['catid']) && isset($catIdMap[(int)$article['catid']])) {
                    $article['catid'] = $catIdMap[(int)$article['catid']];
                }

                $existing = null;
                if ($overwrite && $originalId > 0) {
                    $existing = $this->findExistingArticle($db, $originalId, $article['alias'] ?? '');
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
            } catch (\Exception $e) {
                $result['warnings'][] = sprintf('Error importing article "%s": %s', $article['title'] ?? 'Unknown', $e->getMessage());
                $result['skipped']++;
            }
        }

        return $result;
    }

    protected function importCategoriesOnly(DatabaseInterface $db, array $categories, bool $overwrite): array
    {
        $result = [
            'success' => true, 'imported' => 0, 'skipped' => 0,
            'updated' => 0, 'cats_created' => 0, 'error' => '', 'warnings' => [],
        ];

        usort($categories, fn($a, $b) => ($a['level'] ?? 1) <=> ($b['level'] ?? 1));

        foreach ($categories as $cat) {
            try {
                $id = $this->ensureCategory($db, $cat, $overwrite);
                if ($id) {
                    $result['cats_created']++;
                }
            } catch (\Exception $e) {
                $result['warnings'][] = sprintf('Error importing category "%s": %s', $cat['title'] ?? 'Unknown', $e->getMessage());
                $result['skipped']++;
            }
        }

        $result['imported'] = $result['cats_created'];
        return $result;
    }

    protected function ensureCategory(DatabaseInterface $db, array $cat, bool $overwrite): ?int
    {
        $originalId = (int) ($cat['id'] ?? 0);
        unset($cat['id'], $cat['checked_out'], $cat['checked_out_time'], $cat['asset_id']);

        $alias = $cat['alias'] ?? '';
        $extension = $cat['extension'] ?? 'com_content';

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__categories'))
            ->where($db->quoteName('alias') . ' = :alias')
            ->where($db->quoteName('extension') . ' = :ext')
            ->bind(':alias', $alias)->bind(':ext', $extension)
            ->setLimit(1);
        $db->setQuery($query);
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

    protected function findExistingArticle(DatabaseInterface $db, int $id, string $alias): ?object
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $obj = $db->loadObject();
        if ($obj) {
            return $obj;
        }

        if (!empty($alias)) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('alias') . ' = :alias')
                ->bind(':alias', $alias)
                ->setLimit(1);
            $db->setQuery($query);
            return $db->loadObject();
        }

        return null;
    }

    protected function insertArticle(DatabaseInterface $db, array $data): ?int
    {
        $cols = [
            'title', 'alias', 'introtext', 'fulltext', 'state', 'catid',
            'created', 'created_by', 'created_by_alias', 'modified', 'modified_by',
            'publish_up', 'publish_down', 'images', 'urls', 'attribs', 'version',
            'ordering', 'metakey', 'metadesc', 'access', 'hits', 'metadata',
            'featured', 'language', 'note', 'asset_id',
        ];

        $obj = new \stdClass();
        foreach ($cols as $col) {
            $obj->$col = $data[$col] ?? null;
        }
        $obj->checked_out = 0;
        $obj->checked_out_time = null;

        if ($db->insertObject('#__content', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateArticle(DatabaseInterface $db, array $data): bool
    {
        $cols = [
            'id', 'title', 'alias', 'introtext', 'fulltext', 'state', 'catid',
            'created', 'created_by', 'created_by_alias', 'modified', 'modified_by',
            'publish_up', 'publish_down', 'images', 'urls', 'attribs', 'version',
            'ordering', 'metakey', 'metadesc', 'access', 'hits', 'metadata',
            'featured', 'language', 'note',
        ];

        $obj = new \stdClass();
        foreach ($cols as $col) {
            if (isset($data[$col])) {
                $obj->$col = $data[$col];
            }
        }

        return $db->updateObject('#__content', $obj, 'id');
    }

    protected function insertCategory(DatabaseInterface $db, array $data): ?int
    {
        $cols = [
            'parent_id', 'lft', 'rgt', 'level', 'path', 'extension',
            'title', 'alias', 'note', 'description', 'published', 'access',
            'params', 'metadesc', 'metakey', 'metadata', 'created_user_id',
            'created_time', 'modified_user_id', 'modified_time', 'hits',
            'language', 'version', 'asset_id',
        ];

        $obj = new \stdClass();
        foreach ($cols as $col) {
            $obj->$col = $data[$col] ?? null;
        }
        $obj->checked_out = 0;
        $obj->checked_out_time = null;

        if ($db->insertObject('#__categories', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateCategory(DatabaseInterface $db, array $data): bool
    {
        $cols = [
            'id', 'parent_id', 'level', 'path', 'extension', 'title', 'alias',
            'note', 'description', 'published', 'access', 'params', 'metadesc',
            'metakey', 'metadata', 'language',
        ];

        $obj = new \stdClass();
        foreach ($cols as $col) {
            if (isset($data[$col])) {
                $obj->$col = $data[$col];
            }
        }

        return $db->updateObject('#__categories', $obj, 'id');
    }
}
