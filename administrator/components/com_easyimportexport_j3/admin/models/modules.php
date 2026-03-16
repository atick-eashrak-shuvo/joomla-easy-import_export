<?php

defined('_JEXEC') or die;

jimport('joomla.application.component.model');

class EasyimportexportModelModules extends JModelLegacy
{
    public function getAllModuleIds($clientId = -1)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__modules'));

        if ((int) $clientId >= 0) {
            $query->where($db->quoteName('client_id') . ' = ' . (int) $clientId);
        }

        $db->setQuery($query);

        return (array) $db->loadColumn();
    }

    public function getExportData(array $moduleIds)
    {
        if (empty($moduleIds)) {
            return false;
        }

        $db = JFactory::getDbo();

        JArrayHelper::toInteger($moduleIds);
        $idsList = implode(',', $moduleIds);

        // Load modules
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('id') . ' IN (' . $idsList . ')');

        $db->setQuery($query);
        $modules = (array) $db->loadAssocList();

        if (empty($modules)) {
            return false;
        }

        // Load menu assignments
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__modules_menu'))
            ->where($db->quoteName('moduleid') . ' IN (' . $idsList . ')');

        $db->setQuery($query);
        $menuAssignments = (array) $db->loadAssocList();

        $menuMap = array();

        foreach ($menuAssignments as $assignment) {
            $mid = (int) $assignment['moduleid'];

            if (!isset($menuMap[$mid])) {
                $menuMap[$mid] = array();
            }

            $menuMap[$mid][] = (int) $assignment['menuid'];
        }

        $sppbModuleIds = array();
        $exportModules = array();

        foreach ($modules as $module) {
            $id = (int) $module['id'];

            unset($module['checked_out'], $module['checked_out_time'], $module['asset_id']);

            $module['menu_assignments'] = isset($menuMap[$id]) ? $menuMap[$id] : array();

            if (isset($module['module']) && $module['module'] === 'mod_sppagebuilder') {
                $sppbModuleIds[] = $id;
            }

            $exportModules[] = $module;
        }

        $sppbData = array();

        if (!empty($sppbModuleIds) && $this->hasSppbTable($db)) {
            $sppbData = $this->getSppbDataForModules($db, $sppbModuleIds);
        }

        return array(
            'meta'      => array(
                'format_version' => '1.1',
                'type'           => 'modules',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => JFactory::getApplication()->getCfg('sitename', ''),
                'site_url'       => JUri::root(),
                'module_count'   => count($exportModules),
            ),
            'modules'   => $exportModules,
            'sppb_data' => $sppbData,
        );
    }

    public function importModules(array $data, $overwrite = false)
    {
        $result = array(
            'success'  => true,
            'imported' => 0,
            'skipped'  => 0,
            'updated'  => 0,
            'error'    => '',
            'warnings' => array(),
        );

        $db      = JFactory::getDbo();
        $modules = isset($data['modules']) ? $data['modules'] : array();

        if (empty($modules)) {
            $result['success'] = false;
            $result['error']   = 'No modules found in import file.';

            return $result;
        }

        $validExtensions = $this->getInstalledModuleTypes($db);

        $sppbMap = array();

        foreach (isset($data['sppb_data']) ? $data['sppb_data'] : array() as $sppbRow) {
            $viewId = (int) (isset($sppbRow['view_id']) ? $sppbRow['view_id'] : 0);

            if ($viewId > 0) {
                $sppbMap[$viewId] = $sppbRow;
            }
        }

        foreach ($modules as $moduleData) {
            try {
                $moduleType = isset($moduleData['module']) ? $moduleData['module'] : '';

                if (!in_array($moduleType, $validExtensions, true)) {
                    $result['warnings'][] = sprintf(
                        'Module type "%s" (title: "%s") is not installed on this site. Skipped.',
                        $moduleType,
                        isset($moduleData['title']) ? $moduleData['title'] : 'Unknown'
                    );
                    $result['skipped']++;
                    continue;
                }

                $menuAssignments = isset($moduleData['menu_assignments']) ? $moduleData['menu_assignments'] : array();
                $originalId      = isset($moduleData['id']) ? (int) $moduleData['id'] : 0;

                if ($moduleType === 'mod_sppagebuilder' && isset($sppbMap[$originalId])) {
                    $moduleData['_sppb_data'] = $sppbMap[$originalId];
                }

                unset(
                    $moduleData['id'],
                    $moduleData['menu_assignments'],
                    $moduleData['checked_out'],
                    $moduleData['checked_out_time'],
                    $moduleData['asset_id']
                );

                $existing = null;

                if ($overwrite && $originalId > 0) {
                    $existing = $this->findExistingModule($db, $originalId, $moduleData);
                }

                $sppbContent = isset($moduleData['_sppb_data']) ? $moduleData['_sppb_data'] : null;
                unset($moduleData['_sppb_data']);

                if ($existing) {
                    $moduleData['id'] = (int) $existing->id;
                    $this->updateModule($db, $moduleData);
                    $this->updateMenuAssignments($db, (int) $existing->id, $menuAssignments);

                    if ($moduleType === 'mod_sppagebuilder' && $sppbContent && $this->hasSppbTable($db)) {
                        $this->importSppbData($db, (int) $existing->id, $sppbContent);
                    }

                    $result['updated']++;
                } else {
                    $moduleData['asset_id'] = 0;
                    $newId                  = $this->insertModule($db, $moduleData);

                    if ($newId) {
                        $this->updateMenuAssignments($db, $newId, $menuAssignments);

                        if ($moduleType === 'mod_sppagebuilder' && $sppbContent && $this->hasSppbTable($db)) {
                            $this->importSppbData($db, $newId, $sppbContent);
                        }

                        $result['imported']++;
                    } else {
                        $result['warnings'][] = sprintf(
                            'Failed to import module "%s" (%s).',
                            isset($moduleData['title']) ? $moduleData['title'] : 'Unknown',
                            $moduleType
                        );
                        $result['skipped']++;
                    }
                }
            } catch (Exception $e) {
                $result['warnings'][] = sprintf(
                    'Error importing module "%s": %s',
                    isset($moduleData['title']) ? $moduleData['title'] : 'Unknown',
                    $e->getMessage()
                );
                $result['skipped']++;
            }
        }

        return $result;
    }

    protected function getInstalledModuleTypes(JDatabaseDriver $db)
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('element'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('module'));

        $db->setQuery($query);

        return (array) $db->loadColumn();
    }

    protected function findExistingModule(JDatabaseDriver $db, $originalId, array $moduleData)
    {
        $title      = isset($moduleData['title']) ? $moduleData['title'] : '';
        $moduleType = isset($moduleData['module']) ? $moduleData['module'] : '';

        // Try by ID
        $query = $db->getQuery(true)
            ->select(array($db->quoteName('id'), $db->quoteName('title')))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('id') . ' = ' . (int) $originalId);

        $db->setQuery($query);
        $byId = $db->loadObject();

        if ($byId) {
            return $byId;
        }

        // Fallback by title + module type
        if ($title !== '' && $moduleType !== '') {
            $query = $db->getQuery(true)
                ->select(array($db->quoteName('id'), $db->quoteName('title')))
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('title') . ' = ' . $db->quote($title))
                ->where($db->quoteName('module') . ' = ' . $db->quote($moduleType));

            $db->setQuery($query, 0, 1);

            return $db->loadObject();
        }

        return null;
    }

    protected function insertModule(JDatabaseDriver $db, array $data)
    {
        $columns = array(
            'title', 'note', 'content', 'ordering', 'position',
            'published', 'module', 'access', 'showtitle', 'params',
            'client_id', 'language', 'publish_up', 'publish_down', 'asset_id',
        );

        $obj = new stdClass();

        foreach ($columns as $col) {
            $obj->$col = isset($data[$col]) ? $data[$col] : null;
        }

        $obj->checked_out      = 0;
        $obj->checked_out_time = $db->getNullDate();

        if ($db->insertObject('#__modules', $obj, 'id')) {
            return (int) $obj->id;
        }

        return null;
    }

    protected function updateModule(JDatabaseDriver $db, array $data)
    {
        $columns = array(
            'id', 'title', 'note', 'content', 'ordering', 'position',
            'published', 'module', 'access', 'showtitle', 'params',
            'client_id', 'language', 'publish_up', 'publish_down',
        );

        $obj = new stdClass();

        foreach ($columns as $col) {
            if (isset($data[$col])) {
                $obj->$col = $data[$col];
            }
        }

        return (bool) $db->updateObject('#__modules', $obj, 'id');
    }

    protected function updateMenuAssignments(JDatabaseDriver $db, $moduleId, array $menuIds)
    {
        $moduleId = (int) $moduleId;

        // Delete existing
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__modules_menu'))
            ->where($db->quoteName('moduleid') . ' = ' . $moduleId);

        $db->setQuery($query);
        $db->execute();

        // If no menu IDs, assign to all
        if (empty($menuIds)) {
            $obj            = new stdClass();
            $obj->moduleid  = $moduleId;
            $obj->menuid    = 0;
            $db->insertObject('#__modules_menu', $obj);

            return;
        }

        foreach ($menuIds as $menuId) {
            $obj           = new stdClass();
            $obj->moduleid = $moduleId;
            $obj->menuid   = (int) $menuId;
            $db->insertObject('#__modules_menu', $obj);
        }
    }

    protected function hasSppbTable(JDatabaseDriver $db)
    {
        $tables = $db->getTableList();

        return in_array($db->getPrefix() . 'sppagebuilder', $tables, true);
    }

    protected function getSppbDataForModules(JDatabaseDriver $db, array $moduleIds)
    {
        JArrayHelper::toInteger($moduleIds);
        $idsList = implode(',', $moduleIds);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('mod_sppagebuilder'))
            ->where($db->quoteName('extension_view') . ' = ' . $db->quote('module'))
            ->where($db->quoteName('view_id') . ' IN (' . $idsList . ')');

        $db->setQuery($query);
        $rows = (array) $db->loadAssocList();

        foreach ($rows as &$row) {
            unset($row['checked_out'], $row['checked_out_time'], $row['asset_id']);
        }

        return $rows;
    }

    protected function importSppbData(JDatabaseDriver $db, $newModuleId, array $sppbRow)
    {
        $newModuleId = (int) $newModuleId;

        unset(
            $sppbRow['id'],
            $sppbRow['checked_out'],
            $sppbRow['checked_out_time'],
            $sppbRow['asset_id']
        );

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('extension') . ' = ' . $db->quote('mod_sppagebuilder'))
            ->where($db->quoteName('extension_view') . ' = ' . $db->quote('module'))
            ->where($db->quoteName('view_id') . ' = ' . $newModuleId);

        $db->setQuery($query);
        $existingId = (int) $db->loadResult();

        $sppbRow['view_id'] = $newModuleId;

        $cols = array(
            'title', 'text', 'extension', 'extension_view', 'view_id',
            'active', 'published', 'catid', 'access', 'ordering',
            'created_on', 'created_by', 'modified', 'modified_by',
            'attribs', 'og_title', 'og_image', 'og_description',
            'language', 'hits', 'css', 'content', 'version',
        );

        if ($existingId) {
            $obj      = new stdClass();
            $obj->id  = $existingId;

            foreach ($cols as $col) {
                if (array_key_exists($col, $sppbRow)) {
                    $obj->$col = $sppbRow[$col];
                }
            }

            $db->updateObject('#__sppagebuilder', $obj, 'id');
        } else {
            $obj           = new stdClass();
            $obj->asset_id = 0;

            foreach ($cols as $col) {
                $obj->$col = isset($sppbRow[$col]) ? $sppbRow[$col] : null;
            }

            $obj->checked_out      = 0;
            $obj->checked_out_time = $db->getNullDate();

            $db->insertObject('#__sppagebuilder', $obj);
        }
    }

    public function getModules($clientId = -1, $search = '', $position = '', $state = -3)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select(array(
                $db->quoteName('m.id'),
                $db->quoteName('m.title'),
                $db->quoteName('m.module'),
                $db->quoteName('m.position'),
                $db->quoteName('m.published'),
                $db->quoteName('m.ordering'),
                $db->quoteName('m.client_id'),
                $db->quoteName('m.language'),
                $db->quoteName('m.access'),
                $db->quoteName('m.showtitle'),
                $db->quoteName('ag.title', 'access_level'),
            ))
            ->from($db->quoteName('#__modules', 'm'))
            ->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('m.access'))
            ->order($db->quoteName('m.client_id') . ' ASC, ' . $db->quoteName('m.position') . ' ASC, ' . $db->quoteName('m.ordering') . ' ASC');

        if ((int) $clientId >= 0) {
            $query->where($db->quoteName('m.client_id') . ' = ' . (int) $clientId);
        }

        if ((int) $state > -3) {
            $query->where($db->quoteName('m.published') . ' = ' . (int) $state);
        }

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('m.title') . ' LIKE ' . $db->quote($wild)
                . ' OR ' . $db->quoteName('m.module') . ' LIKE ' . $db->quote($wild)
                . ' OR ' . $db->quoteName('m.position') . ' LIKE ' . $db->quote($wild) . ')'
            );
        }

        if (!empty($position)) {
            $query->where($db->quoteName('m.position') . ' = ' . $db->quote($position));
        }

        $db->setQuery($query);

        return (array) $db->loadObjectList();
    }

    public function getPositions()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('position'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('position') . ' != ' . $db->quote(''))
            ->order($db->quoteName('position') . ' ASC');

        $db->setQuery($query);

        return (array) $db->loadColumn();
    }
}

