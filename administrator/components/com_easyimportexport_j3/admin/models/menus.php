<?php

defined('_JEXEC') or die;

jimport('joomla.application.component.model');

class EasyimportexportModelMenus extends JModelLegacy
{
    public function getAllSiteMenuItemIds()
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('client_id') . ' = 0')
            ->where($db->quoteName('id') . ' > 1');

        $db->setQuery($query);

        return (array) $db->loadColumn();
    }

    public function getExportData(array $menuItemIds)
    {
        if (empty($menuItemIds)) {
            return false;
        }

        $db = JFactory::getDbo();

        JArrayHelper::toInteger($menuItemIds);
        $idsList = implode(',', $menuItemIds);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('id') . ' IN (' . $idsList . ')')
            ->where($db->quoteName('client_id') . ' = 0');

        $db->setQuery($query);
        $items = (array) $db->loadAssocList();

        if (empty($items)) {
            return false;
        }

        $menutypes = array();
        foreach ($items as $item) {
            if (!empty($item['menutype'])) {
                $menutypes[] = $item['menutype'];
            }
        }
        $menutypes = array_unique($menutypes);

        $types = array();
        if (!empty($menutypes)) {
            $quoted = array();
            foreach ($menutypes as $mt) {
                $quoted[] = $db->quote($mt);
            }
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__menu_types'))
                ->where($db->quoteName('menutype') . ' IN (' . implode(',', $quoted) . ')');

            $db->setQuery($query);
            $types = (array) $db->loadAssocList();
        }

        foreach ($items as &$item) {
            unset($item['checked_out'], $item['checked_out_time'], $item['asset_id']);
        }
        foreach ($types as &$type) {
            unset($type['asset_id']);
        }

        return array(
            'meta'       => array(
                'format_version' => '1.0',
                'type'           => 'menus',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => JFactory::getApplication()->getCfg('sitename', ''),
                'site_url'       => JUri::root(),
                'item_count'     => count($items),
            ),
            'menu_types' => $types,
            'menu_items' => $items,
        );
    }

    public function importMenus(array $data, $overwrite = false)
    {
        $result = array(
            'success'  => true,
            'imported' => 0,
            'skipped'  => 0,
            'updated'  => 0,
            'error'    => '',
            'warnings' => array(),
        );

        $db        = JFactory::getDbo();
        $menuTypes = isset($data['menu_types']) ? $data['menu_types'] : array();
        $menuItems = isset($data['menu_items']) ? $data['menu_items'] : array();

        if (empty($menuItems)) {
            $result['success'] = false;
            $result['error']   = 'No menu items found in import file.';
            return $result;
        }

        foreach ($menuTypes as $type) {
            $this->ensureMenuType($db, $type);
        }

        $installedComponents = $this->getInstalledComponents($db);
        $idMap = array();

        usort($menuItems, array($this, 'sortByLevel'));

        foreach ($menuItems as $item) {
            try {
                $originalId = isset($item['id']) ? (int) $item['id'] : 0;

                $itemType = isset($item['type']) ? $item['type'] : 'component';
                if ($itemType === 'component') {
                    $link = isset($item['link']) ? $item['link'] : '';
                    $componentOption = $this->extractOptionFromLink($link);
                    if (!empty($componentOption) && !in_array($componentOption, $installedComponents, true)) {
                        $result['warnings'][] = sprintf(
                            'Menu item "%s" requires component "%s" which is not installed. Skipped.',
                            isset($item['title']) ? $item['title'] : 'Unknown',
                            $componentOption
                        );
                        $result['skipped']++;
                        continue;
                    }
                }

                unset($item['id'], $item['checked_out'], $item['checked_out_time'], $item['asset_id']);

                if (isset($item['parent_id']) && isset($idMap[(int) $item['parent_id']])) {
                    $item['parent_id'] = $idMap[(int) $item['parent_id']];
                }

                $existing = null;
                if ($overwrite && $originalId > 0) {
                    $existing = $this->findExistingMenuItem($db, $originalId, isset($item['alias']) ? $item['alias'] : '', isset($item['menutype']) ? $item['menutype'] : '');
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
            } catch (Exception $e) {
                $title = isset($item['title']) ? $item['title'] : 'Unknown';
                $result['warnings'][] = sprintf('Error importing menu "%s": %s', $title, $e->getMessage());
                $result['skipped']++;
            }
        }

        return $result;
    }

    public function sortByLevel($a, $b)
    {
        $la = isset($a['level']) ? (int) $a['level'] : 1;
        $lb = isset($b['level']) ? (int) $b['level'] : 1;
        return $la - $lb;
    }

    protected function ensureMenuType($db, array $type)
    {
        $menutype = isset($type['menutype']) ? $type['menutype'] : '';
        if (empty($menutype)) {
            return;
        }

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__menu_types'))
            ->where($db->quoteName('menutype') . ' = ' . $db->quote($menutype));
        $db->setQuery($query);

        if (!$db->loadResult()) {
            $obj              = new stdClass();
            $obj->menutype    = $menutype;
            $obj->title       = isset($type['title']) ? $type['title'] : $menutype;
            $obj->description = isset($type['description']) ? $type['description'] : '';
            $obj->client_id   = isset($type['client_id']) ? (int) $type['client_id'] : 0;
            $obj->ordering    = isset($type['ordering']) ? (int) $type['ordering'] : 0;
            $obj->asset_id    = 0;
            $db->insertObject('#__menu_types', $obj);
        }
    }

    protected function findExistingMenuItem($db, $id, $alias, $menutype)
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__menu'))
            ->where($db->quoteName('id') . ' = ' . (int) $id);
        $db->setQuery($query);
        $obj = $db->loadObject();
        if ($obj) {
            return $obj;
        }

        if (!empty($alias) && !empty($menutype)) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__menu'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($alias))
                ->where($db->quoteName('menutype') . ' = ' . $db->quote($menutype))
                ->where($db->quoteName('client_id') . ' = 0');
            $db->setQuery($query, 0, 1);
            return $db->loadObject();
        }

        return null;
    }

    protected function insertMenuItem($db, array $data)
    {
        $cols = array(
            'menutype', 'title', 'alias', 'note', 'path', 'link', 'type',
            'published', 'parent_id', 'level', 'component_id', 'browserNav',
            'access', 'img', 'template_style_id', 'params', 'home', 'language',
            'client_id', 'publish_up', 'publish_down', 'lft', 'rgt', 'asset_id',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            $obj->$col = isset($data[$col]) ? $data[$col] : null;
        }
        $obj->checked_out      = 0;
        $obj->checked_out_time = $db->getNullDate();

        if ($db->insertObject('#__menu', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateMenuItem($db, array $data)
    {
        $cols = array(
            'id', 'menutype', 'title', 'alias', 'note', 'path', 'link', 'type',
            'published', 'parent_id', 'level', 'component_id', 'browserNav',
            'access', 'img', 'template_style_id', 'params', 'home', 'language',
            'client_id', 'publish_up', 'publish_down',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            if (isset($data[$col])) {
                $obj->$col = $data[$col];
            }
        }

        return $db->updateObject('#__menu', $obj, 'id');
    }

    public function getMenuTypes()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__menu_types'))
            ->order($db->quoteName('title') . ' ASC');

        $db->setQuery($query);

        return (array) $db->loadObjectList();
    }

    public function getMenuItems($menutype = '', $search = '', $state = -3)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select(array(
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
            ))
            ->from($db->quoteName('#__menu', 'm'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('m.access'))
            ->where($db->quoteName('m.client_id') . ' = 0')
            ->where($db->quoteName('m.id') . ' > 1')
            ->order($db->quoteName('m.menutype') . ' ASC, ' . $db->quoteName('m.lft') . ' ASC');

        if (!empty($menutype)) {
            $query->where($db->quoteName('m.menutype') . ' = ' . $db->quote($menutype));
        }

        if ((int) $state > -3) {
            $query->where($db->quoteName('m.published') . ' = ' . (int) $state);
        }

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('m.title') . ' LIKE ' . $db->quote($wild)
                . ' OR ' . $db->quoteName('m.alias') . ' LIKE ' . $db->quote($wild)
                . ' OR ' . $db->quoteName('m.link') . ' LIKE ' . $db->quote($wild) . ')'
            );
        }

        $db->setQuery($query);

        return (array) $db->loadObjectList();
    }

    protected function getInstalledComponents($db)
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('element'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
        $db->setQuery($query);
        return $db->loadColumn();
    }

    protected function extractOptionFromLink($link)
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

