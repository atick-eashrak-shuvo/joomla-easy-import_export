<?php

namespace Joomla\Component\Easyimportexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

class MenusModel extends BaseDatabaseModel
{
    protected function filterColumns(DatabaseInterface $db, string $table, array $data): array
    {
        static $columnCache = [];
        if (!isset($columnCache[$table])) {
            $columnCache[$table] = array_keys($db->getTableColumns($table));
        }
        return array_intersect_key($data, array_flip($columnCache[$table]));
    }

    public function getMenuTypes(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__menu_types'))
            ->order($db->quoteName('title') . ' ASC');

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getMenuItems(string $menutype = '', string $search = '', int $state = -3): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('m.id'),
                $db->quoteName('m.menutype'),
                $db->quoteName('m.title'),
                $db->quoteName('m.alias'),
                $db->quoteName('m.link'),
                $db->quoteName('m.type'),
                $db->quoteName('m.published'),
                $db->quoteName('m.parent_id'),
                $db->quoteName('m.level'),
                $db->quoteName('m.language'),
                $db->quoteName('m.access'),
                $db->quoteName('m.home'),
                $db->quoteName('m.client_id'),
                $db->quoteName('ag.title', 'access_level'),
            ])
            ->from($db->quoteName('#__menu', 'm'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('m.access'))
            ->where($db->quoteName('m.client_id') . ' = 0')
            ->where($db->quoteName('m.id') . ' > 1')
            ->order($db->quoteName('m.menutype') . ' ASC, ' . $db->quoteName('m.lft') . ' ASC');

        if (!empty($menutype)) {
            $query->where($db->quoteName('m.menutype') . ' = :menutype')
                ->bind(':menutype', $menutype);
        }

        if ($state > -3) {
            $query->where($db->quoteName('m.published') . ' = :state')
                ->bind(':state', $state, \Joomla\Database\ParameterType::INTEGER);
        }

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('m.title') . ' LIKE :s1'
                . ' OR ' . $db->quoteName('m.alias') . ' LIKE :s2'
                . ' OR ' . $db->quoteName('m.link') . ' LIKE :s3)'
            )
            ->bind(':s1', $wild)->bind(':s2', $wild)->bind(':s3', $wild);
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getExportData(array $menuItemIds): array|false
    {
        if (empty($menuItemIds)) {
            return false;
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__menu'))
            ->whereIn($db->quoteName('id'), $menuItemIds)
            ->where($db->quoteName('client_id') . ' = 0');
        $db->setQuery($query);
        $items = $db->loadAssocList();

        if (empty($items)) {
            return false;
        }

        $menutypes = array_unique(array_column($items, 'menutype'));
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__menu_types'))
            ->whereIn($db->quoteName('menutype'), $menutypes, \Joomla\Database\ParameterType::STRING);
        $db->setQuery($query);
        $types = $db->loadAssocList();

        foreach ($items as &$item) {
            unset($item['checked_out'], $item['checked_out_time'], $item['asset_id']);
        }
        foreach ($types as &$type) {
            unset($type['asset_id']);
        }

        return [
            'meta' => [
                'format_version' => '1.0',
                'type'           => 'menus',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => Factory::getApplication()->get('sitename', ''),
                'site_url'       => (string) \Joomla\CMS\Uri\Uri::root(),
                'item_count'     => count($items),
            ],
            'menu_types' => $types,
            'menu_items' => $items,
        ];
    }

    public function getExportDataByMenutype(string $menutype): array|false
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('menutype') . ' = :mt')
            ->where($db->quoteName('client_id') . ' = 0')
            ->where($db->quoteName('id') . ' > 1')
            ->bind(':mt', $menutype);
        $db->setQuery($query);
        $ids = $db->loadColumn();

        return $this->getExportData($ids);
    }

    public function getAllSiteMenuItemIds(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('client_id') . ' = 0')
            ->where($db->quoteName('id') . ' > 1');
        $db->setQuery($query);
        return $db->loadColumn();
    }

    public function importMenus(array $data, bool $overwrite = false): array
    {
        $result = [
            'success' => true, 'imported' => 0, 'skipped' => 0,
            'updated' => 0, 'error' => '', 'warnings' => [],
        ];

        $db = $this->getDatabase();
        $menuTypes = $data['menu_types'] ?? [];
        $menuItems = $data['menu_items'] ?? [];

        if (empty($menuItems)) {
            $result['success'] = false;
            $result['error'] = 'No menu items found in import file.';
            return $result;
        }

        foreach ($menuTypes as $type) {
            $this->ensureMenuType($db, $type);
        }

        $installedComponents = $this->getInstalledComponents($db);
        $idMap = [];

        usort($menuItems, fn($a, $b) => ($a['level'] ?? 1) <=> ($b['level'] ?? 1));

        foreach ($menuItems as $item) {
            try {
                $originalId = (int) ($item['id'] ?? 0);
                $menuAssignments = [];

                $itemType = $item['type'] ?? 'component';
                if ($itemType === 'component') {
                    $link = $item['link'] ?? '';
                    $componentOption = $this->extractOptionFromLink($link);
                    if (!empty($componentOption) && !in_array($componentOption, $installedComponents, true)) {
                        $result['warnings'][] = sprintf(
                            'Menu item "%s" requires component "%s" which is not installed. Skipped.',
                            $item['title'] ?? 'Unknown',
                            $componentOption
                        );
                        $result['skipped']++;
                        continue;
                    }
                }

                unset($item['id'], $item['checked_out'], $item['checked_out_time'], $item['asset_id']);

                if (isset($item['parent_id']) && isset($idMap[(int)$item['parent_id']])) {
                    $item['parent_id'] = $idMap[(int)$item['parent_id']];
                }

                $existing = null;
                if ($overwrite && $originalId > 0) {
                    $existing = $this->findExistingMenuItem($db, $originalId, $item['alias'] ?? '', $item['menutype'] ?? '');
                }

                if ($existing) {
                    $item['id'] = (int) $existing->id;
                    $this->updateMenuItem($db, $item);
                    $idMap[$originalId] = (int) $existing->id;
                    $result['updated']++;
                } else {
                    $item['asset_id'] = 0;
                    $newId = $this->insertMenuItem($db, $item);
                    if ($newId) {
                        $idMap[$originalId] = $newId;
                        $result['imported']++;
                    } else {
                        $result['skipped']++;
                    }
                }
            } catch (\Exception $e) {
                $result['warnings'][] = sprintf('Error importing menu "%s": %s', $item['title'] ?? 'Unknown', $e->getMessage());
                $result['skipped']++;
            }
        }

        return $result;
    }

    protected function ensureMenuType(DatabaseInterface $db, array $type): void
    {
        $menutype = $type['menutype'] ?? '';
        if (empty($menutype)) {
            return;
        }

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__menu_types'))
            ->where($db->quoteName('menutype') . ' = :mt')
            ->bind(':mt', $menutype);
        $db->setQuery($query);

        if (!$db->loadResult()) {
            $obj = new \stdClass();
            $obj->menutype = $menutype;
            $obj->title = $type['title'] ?? $menutype;
            $obj->description = $type['description'] ?? '';
            $obj->client_id = (int) ($type['client_id'] ?? 0);
            $obj->ordering = (int) ($type['ordering'] ?? 0);
            $obj->asset_id = 0;
            $db->insertObject('#__menu_types', $obj);
        }
    }

    protected function findExistingMenuItem(DatabaseInterface $db, int $id, string $alias, string $menutype): ?object
    {
        $query = $db->getQuery(true)
            ->select([$db->quoteName('id')])
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $obj = $db->loadObject();
        if ($obj) {
            return $obj;
        }

        if (!empty($alias) && !empty($menutype)) {
            $query = $db->getQuery(true)
                ->select([$db->quoteName('id')])
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('alias') . ' = :alias')
                ->where($db->quoteName('menutype') . ' = :mt')
                ->where($db->quoteName('client_id') . ' = 0')
                ->bind(':alias', $alias)->bind(':mt', $menutype)
                ->setLimit(1);
            $db->setQuery($query);
            return $db->loadObject();
        }

        return null;
    }

    protected function insertMenuItem(DatabaseInterface $db, array $data): ?int
    {
        $data = $this->filterColumns($db, '#__menu', $data);
        unset($data['id']);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }
        $obj->checked_out = 0;
        $obj->checked_out_time = null;

        if ($db->insertObject('#__menu', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateMenuItem(DatabaseInterface $db, array $data): bool
    {
        $data = $this->filterColumns($db, '#__menu', $data);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }

        return $db->updateObject('#__menu', $obj, 'id');
    }

    protected function getInstalledComponents(DatabaseInterface $db): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('element'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        return $db->loadColumn();
    }

    protected function extractOptionFromLink(string $link): string
    {
        if (empty($link)) {
            return '';
        }
        if (preg_match('/[?&]option=([a-zA-Z0-9_]+)/', $link, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
