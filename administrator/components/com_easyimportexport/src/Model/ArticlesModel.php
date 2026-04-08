<?php

namespace Joomla\Component\Easyimportexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

class ArticlesModel extends BaseDatabaseModel
{
    protected function filterColumns(DatabaseInterface $db, string $table, array $data): array
    {
        static $columnCache = [];
        if (!isset($columnCache[$table])) {
            $columnCache[$table] = array_keys($db->getTableColumns($table));
        }
        return array_intersect_key($data, array_flip($columnCache[$table]));
    }

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

        $mediaPaths = $this->collectMediaPaths($articles);

        return [
            'meta' => [
                'format_version' => '2.0',
                'type'           => 'articles',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => Factory::getApplication()->get('sitename', ''),
                'site_url'       => (string) \Joomla\CMS\Uri\Uri::root(),
                'article_count'  => count($articles),
                'category_count' => count($categories),
                'media_count'    => count($mediaPaths),
            ],
            'categories'  => $categories,
            'articles'    => $articles,
            'media_files' => $mediaPaths,
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

    public function importArticles(array $data, bool $overwrite = false, string $mediaDir = ''): array
    {
        $result = [
            'success' => true, 'imported' => 0, 'skipped' => 0,
            'updated' => 0, 'cats_created' => 0, 'media_written' => 0,
            'error' => '', 'warnings' => [],
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
                        $this->ensureArticleAsset($db, $newId, $article['title'] ?? '', (int) ($article['catid'] ?? 0));
                        $this->ensureWorkflowAssociation($db, $newId);
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

        if (!empty($mediaDir) && is_dir($mediaDir)) {
            $mediaResult = $this->writeMediaFromDirectory($mediaDir);
            $result['media_written'] = $mediaResult['written'];
            foreach ($mediaResult['warnings'] as $w) {
                $result['warnings'][] = $w;
            }
        } elseif (!empty($data['media'])) {
            $mediaResult = $this->writeMediaFromBase64($data['media']);
            $result['media_written'] = $mediaResult['written'];
            foreach ($mediaResult['warnings'] as $w) {
                $result['warnings'][] = $w;
            }
        }

        return $result;
    }

    protected function ensureArticleAsset(DatabaseInterface $db, int $articleId, string $title, int $catId): void
    {
        $assetName = 'com_content.article.' . $articleId;

        $parentAssetId = 1;
        if ($catId > 0) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('asset_id'))
                ->from($db->quoteName('#__categories'))
                ->where($db->quoteName('id') . ' = :catid')
                ->bind(':catid', $catId, \Joomla\Database\ParameterType::INTEGER);
            $db->setQuery($query);
            $catAssetId = (int) $db->loadResult();
            if ($catAssetId > 0) {
                $parentAssetId = $catAssetId;
            }
        }

        if ($parentAssetId <= 1) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__assets'))
                ->where($db->quoteName('name') . ' = ' . $db->quote('com_content'));
            $db->setQuery($query);
            $componentAssetId = (int) $db->loadResult();
            if ($componentAssetId > 0) {
                $parentAssetId = $componentAssetId;
            }
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('name') . ' = :name')
            ->bind(':name', $assetName);
        $db->setQuery($query);

        if ($db->loadResult()) {
            return;
        }

        $query = $db->getQuery(true)
            ->select([$db->quoteName('lft'), $db->quoteName('rgt')])
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('id') . ' = :pid')
            ->bind(':pid', $parentAssetId, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $parent = $db->loadObject();

        $lft = $parent ? (int) $parent->rgt : 0;
        $rgt = $lft + 1;

        if ($parent) {
            $db->setQuery('UPDATE ' . $db->quoteName('#__assets') . ' SET ' . $db->quoteName('rgt') . ' = ' . $db->quoteName('rgt') . ' + 2 WHERE ' . $db->quoteName('rgt') . ' >= ' . $lft);
            $db->execute();
            $db->setQuery('UPDATE ' . $db->quoteName('#__assets') . ' SET ' . $db->quoteName('lft') . ' = ' . $db->quoteName('lft') . ' + 2 WHERE ' . $db->quoteName('lft') . ' > ' . $lft);
            $db->execute();
        }

        $asset = new \stdClass();
        $asset->parent_id = $parentAssetId;
        $asset->lft = $lft;
        $asset->rgt = $rgt;
        $asset->level = $parent ? 4 : 1;
        $asset->name = $assetName;
        $asset->title = $title;
        $asset->rules = '{}';
        $db->insertObject('#__assets', $asset, 'id');

        $db->setQuery('UPDATE ' . $db->quoteName('#__content') . ' SET ' . $db->quoteName('asset_id') . ' = ' . (int) $asset->id . ' WHERE ' . $db->quoteName('id') . ' = ' . $articleId);
        $db->execute();
    }

    protected function ensureWorkflowAssociation(DatabaseInterface $db, int $articleId): void
    {
        $tables = $db->getTableList();
        $prefix = $db->getPrefix();

        if (!in_array($prefix . 'workflow_associations', $tables, true)) {
            return;
        }

        $ext = 'com_content.article';
        $query = $db->getQuery(true)
            ->select($db->quoteName('item_id'))
            ->from($db->quoteName('#__workflow_associations'))
            ->where($db->quoteName('item_id') . ' = :aid')
            ->where($db->quoteName('extension') . ' = :ext')
            ->bind(':aid', $articleId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':ext', $ext);
        $db->setQuery($query);

        if ($db->loadResult()) {
            return;
        }

        $defaultStageId = 1;
        $query = $db->getQuery(true)
            ->select($db->quoteName('s.id'))
            ->from($db->quoteName('#__workflow_stages', 's'))
            ->join('INNER', $db->quoteName('#__workflows', 'w'), $db->quoteName('w.id') . ' = ' . $db->quoteName('s.workflow_id'))
            ->where($db->quoteName('w.default') . ' = 1')
            ->where($db->quoteName('w.published') . ' = 1')
            ->where($db->quoteName('s.default') . ' = 1')
            ->setLimit(1);
        $db->setQuery($query);
        $stageId = (int) $db->loadResult();

        if ($stageId > 0) {
            $defaultStageId = $stageId;
        }

        $assoc = new \stdClass();
        $assoc->item_id = $articleId;
        $assoc->stage_id = $defaultStageId;
        $assoc->extension = $ext;
        $db->insertObject('#__workflow_associations', $assoc);
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
        $newId = $this->insertCategory($db, $cat);
        if ($newId) {
            $this->ensureCategoryAsset($db, $newId, $cat['title'] ?? '', $extension);
        }
        return $newId;
    }

    protected function ensureCategoryAsset(DatabaseInterface $db, int $catId, string $title, string $extension): void
    {
        $assetName = $extension . '.category.' . $catId;

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('name') . ' = :name')
            ->bind(':name', $assetName);
        $db->setQuery($query);

        if ($db->loadResult()) {
            return;
        }

        $parentAssetId = 1;
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('name') . ' = :ext')
            ->bind(':ext', $extension);
        $db->setQuery($query);
        $extAssetId = (int) $db->loadResult();
        if ($extAssetId > 0) {
            $parentAssetId = $extAssetId;
        }

        $query = $db->getQuery(true)
            ->select([$db->quoteName('lft'), $db->quoteName('rgt')])
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('id') . ' = :pid')
            ->bind(':pid', $parentAssetId, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $parent = $db->loadObject();

        $lft = $parent ? (int) $parent->rgt : 0;
        $rgt = $lft + 1;

        if ($parent) {
            $db->setQuery('UPDATE ' . $db->quoteName('#__assets') . ' SET ' . $db->quoteName('rgt') . ' = ' . $db->quoteName('rgt') . ' + 2 WHERE ' . $db->quoteName('rgt') . ' >= ' . $lft);
            $db->execute();
            $db->setQuery('UPDATE ' . $db->quoteName('#__assets') . ' SET ' . $db->quoteName('lft') . ' = ' . $db->quoteName('lft') . ' + 2 WHERE ' . $db->quoteName('lft') . ' > ' . $lft);
            $db->execute();
        }

        $asset = new \stdClass();
        $asset->parent_id = $parentAssetId;
        $asset->lft = $lft;
        $asset->rgt = $rgt;
        $asset->level = 3;
        $asset->name = $assetName;
        $asset->title = $title;
        $asset->rules = '{}';
        $db->insertObject('#__assets', $asset, 'id');

        $db->setQuery('UPDATE ' . $db->quoteName('#__categories') . ' SET ' . $db->quoteName('asset_id') . ' = ' . (int) $asset->id . ' WHERE ' . $db->quoteName('id') . ' = ' . $catId);
        $db->execute();
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
        $data = $this->filterColumns($db, '#__content', $data);
        unset($data['id']);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
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
        $data = $this->filterColumns($db, '#__content', $data);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }

        return $db->updateObject('#__content', $obj, 'id');
    }

    protected function insertCategory(DatabaseInterface $db, array $data): ?int
    {
        $data = $this->filterColumns($db, '#__categories', $data);
        unset($data['id']);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
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
        $data = $this->filterColumns($db, '#__categories', $data);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }

        return $db->updateObject('#__categories', $obj, 'id');
    }

    // --- Media helpers ---

    public function collectMediaPaths(array $articles): array
    {
        $paths = [];

        foreach ($articles as $article) {
            if (!empty($article['images'])) {
                $imgs = json_decode($article['images'], true);
                if (is_array($imgs)) {
                    foreach (['image_intro', 'image_fulltext'] as $key) {
                        if (!empty($imgs[$key])) {
                            $paths[$imgs[$key]] = true;
                        }
                    }
                }
            }

            foreach (['introtext', 'fulltext'] as $field) {
                if (!empty($article[$field])) {
                    $this->extractInlineImages($article[$field], $paths);
                }
            }
        }

        return $this->validateMediaPaths(array_keys($paths));
    }

    protected function extractInlineImages(string $html, array &$paths): void
    {
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $src) {
                if ($this->isLocalPath($src)) {
                    $paths[$src] = true;
                }
            }
        }

        if (preg_match_all('/background[-\w]*:\s*url\(["\']?([^"\')\s]+)["\']?\)/i', $html, $matches)) {
            foreach ($matches[1] as $src) {
                if ($this->isLocalPath($src)) {
                    $paths[$src] = true;
                }
            }
        }
    }

    protected function isLocalPath(string $path): bool
    {
        if (preg_match('#^https?://#i', $path) || preg_match('#^//#', $path) || preg_match('#^data:#i', $path)) {
            return false;
        }
        return true;
    }

    protected function validateMediaPaths(array $relativePaths): array
    {
        $valid = [];
        $root = JPATH_ROOT . '/';
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
        $maxSize = 10 * 1024 * 1024;

        foreach ($relativePaths as $relPath) {
            $relPath = ltrim($relPath, '/');
            $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt, true)) {
                continue;
            }

            $absPath = realpath($root . $relPath);

            if (!$absPath || strpos($absPath, realpath($root)) !== 0 || !is_file($absPath)) {
                continue;
            }

            $size = filesize($absPath);
            if ($size > $maxSize || $size === 0) {
                continue;
            }

            $valid[] = $relPath;
        }

        return $valid;
    }

    public function writeMediaFromDirectory(string $mediaDir): array
    {
        $result = ['written' => 0, 'skipped' => 0, 'warnings' => []];
        $root = JPATH_ROOT . '/';
        $mediaDirReal = realpath($mediaDir);

        if (!$mediaDirReal || !is_dir($mediaDirReal)) {
            return $result;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($mediaDirReal, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            $srcPath = $fileInfo->getPathname();
            $relPath = substr($srcPath, strlen($mediaDirReal) + 1);
            $relPath = str_replace('\\', '/', $relPath);

            if (strpos($relPath, '..') !== false) {
                $result['warnings'][] = sprintf('Skipped suspicious path: %s', $relPath);
                $result['skipped']++;
                continue;
            }

            $destPath = $root . $relPath;
            $destDir = dirname($destPath);

            if (!is_dir($destDir)) {
                if (!mkdir($destDir, 0755, true)) {
                    $result['warnings'][] = sprintf('Could not create directory for: %s', $relPath);
                    $result['skipped']++;
                    continue;
                }
            }

            if (copy($srcPath, $destPath)) {
                $result['written']++;
            } else {
                $result['warnings'][] = sprintf('Could not write file: %s', $relPath);
                $result['skipped']++;
            }
        }

        return $result;
    }

    public function writeMediaFromBase64(array $media): array
    {
        $result = ['written' => 0, 'skipped' => 0, 'warnings' => []];
        $root = JPATH_ROOT . '/';

        foreach ($media as $file) {
            $relPath = $file['path'] ?? '';
            $data = $file['data'] ?? '';

            if (empty($relPath) || empty($data)) {
                $result['skipped']++;
                continue;
            }

            $relPath = ltrim($relPath, '/');

            if (strpos($relPath, '..') !== false) {
                $result['warnings'][] = sprintf('Skipped suspicious path: %s', $relPath);
                $result['skipped']++;
                continue;
            }

            $absPath = $root . $relPath;
            $dir = dirname($absPath);

            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $result['warnings'][] = sprintf('Could not create directory: %s', $dir);
                    $result['skipped']++;
                    continue;
                }
            }

            $decoded = base64_decode($data, true);
            if ($decoded === false) {
                $result['warnings'][] = sprintf('Invalid base64 data for: %s', $relPath);
                $result['skipped']++;
                continue;
            }

            if (file_put_contents($absPath, $decoded) !== false) {
                $result['written']++;
            } else {
                $result['warnings'][] = sprintf('Could not write file: %s', $relPath);
                $result['skipped']++;
            }
        }

        return $result;
    }
}
