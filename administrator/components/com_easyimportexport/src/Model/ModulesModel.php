<?php

namespace Joomla\Component\Easyimportexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

class ModulesModel extends BaseDatabaseModel
{
    protected function filterColumns(DatabaseInterface $db, string $table, array $data): array
    {
        static $columnCache = [];
        if (!isset($columnCache[$table])) {
            $columnCache[$table] = array_keys($db->getTableColumns($table));
        }
        return array_intersect_key($data, array_flip($columnCache[$table]));
    }

    public function getModules(int $clientId = -1, string $search = '', string $position = '', int $state = -3): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
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
        ])
        ->from($db->quoteName('#__modules', 'm'))
        ->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('m.access'))
        ->order($db->quoteName('m.client_id') . ' ASC, ' . $db->quoteName('m.position') . ' ASC, ' . $db->quoteName('m.ordering') . ' ASC');

        if ($clientId >= 0) {
            $query->where($db->quoteName('m.client_id') . ' = :clientId')
                ->bind(':clientId', $clientId, \Joomla\Database\ParameterType::INTEGER);
        }

        if ($state > -3) {
            $query->where($db->quoteName('m.published') . ' = :state')
                ->bind(':state', $state, \Joomla\Database\ParameterType::INTEGER);
        }

        if (!empty($search)) {
            $searchWild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('m.title') . ' LIKE :search1'
                . ' OR ' . $db->quoteName('m.module') . ' LIKE :search2'
                . ' OR ' . $db->quoteName('m.position') . ' LIKE :search3)'
            )
            ->bind(':search1', $searchWild)
            ->bind(':search2', $searchWild)
            ->bind(':search3', $searchWild);
        }

        if (!empty($position)) {
            $query->where($db->quoteName('m.position') . ' = :position')
                ->bind(':position', $position);
        }

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getPositions(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('DISTINCT ' . $db->quoteName('position'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('position') . ' != ' . $db->quote(''))
            ->order($db->quoteName('position') . ' ASC');

        $db->setQuery($query);
        return $db->loadColumn();
    }

    public function getAllModuleIds(int $clientId = -1): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__modules'));

        if ($clientId >= 0) {
            $query->where($db->quoteName('client_id') . ' = :clientId')
                ->bind(':clientId', $clientId, \Joomla\Database\ParameterType::INTEGER);
        }

        $db->setQuery($query);
        return $db->loadColumn();
    }

    public function getExportData(array $moduleIds): array|false
    {
        if (empty($moduleIds)) {
            return false;
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__modules'))
            ->whereIn($db->quoteName('id'), $moduleIds);

        $db->setQuery($query);
        $modules = $db->loadAssocList();

        if (empty($modules)) {
            return false;
        }

        $menuQuery = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__modules_menu'))
            ->whereIn($db->quoteName('moduleid'), $moduleIds);

        $db->setQuery($menuQuery);
        $menuAssignments = $db->loadAssocList();

        $menuMap = [];
        foreach ($menuAssignments as $assignment) {
            $menuMap[$assignment['moduleid']][] = (int) $assignment['menuid'];
        }

        $sppbModuleIds = [];
        $exportModules = [];
        foreach ($modules as $module) {
            $id = (int) $module['id'];

            unset(
                $module['checked_out'],
                $module['checked_out_time'],
                $module['asset_id']
            );

            $module['menu_assignments'] = $menuMap[$id] ?? [];

            if ($module['module'] === 'mod_sppagebuilder') {
                $sppbModuleIds[] = $id;
            }

            $exportModules[] = $module;
        }

        $sppbData = [];
        if (!empty($sppbModuleIds)) {
            $sppbData = $this->getSppbDataForModules($db, $sppbModuleIds);
        }

        return [
            'meta' => [
                'format_version' => '1.1',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => Factory::getApplication()->get('sitename', ''),
                'site_url'       => (string) \Joomla\CMS\Uri\Uri::root(),
                'module_count'   => count($exportModules),
            ],
            'modules'   => $exportModules,
            'sppb_data' => $sppbData,
        ];
    }

    public function importModules(array $data, bool $overwrite = false): array
    {
        $result = [
            'success'  => true,
            'imported' => 0,
            'skipped'  => 0,
            'updated'  => 0,
            'error'    => '',
            'warnings' => [],
        ];

        $db = $this->getDatabase();
        $modules = $data['modules'] ?? [];

        if (empty($modules)) {
            $result['success'] = false;
            $result['error'] = 'No modules found in import file.';
            return $result;
        }

        $validExtensions = $this->getInstalledModuleTypes();

        $sppbMap = [];
        foreach ($data['sppb_data'] ?? [] as $sppbRow) {
            $viewId = (int) ($sppbRow['view_id'] ?? 0);
            if ($viewId > 0) {
                $sppbMap[$viewId] = $sppbRow;
            }
        }

        foreach ($modules as $moduleData) {
            try {
                $moduleType = $moduleData['module'] ?? '';

                if (!in_array($moduleType, $validExtensions)) {
                    $result['warnings'][] = sprintf(
                        'Module type "%s" (title: "%s") is not installed on this site. Skipped.',
                        $moduleType,
                        $moduleData['title'] ?? 'Unknown'
                    );
                    $result['skipped']++;
                    continue;
                }

                $menuAssignments = $moduleData['menu_assignments'] ?? [];
                $originalId = isset($moduleData['id']) ? (int) $moduleData['id'] : 0;

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
                    $existing = $this->findExistingModule($originalId, $moduleData['title'], $moduleData['module']);
                }

                $sppbContent = $moduleData['_sppb_data'] ?? null;
                unset($moduleData['_sppb_data']);

                if ($existing) {
                    $moduleData['id'] = (int) $existing->id;
                    $this->updateModule($db, $moduleData);
                    $this->updateMenuAssignments($db, (int) $existing->id, $menuAssignments);
                    if ($moduleType === 'mod_sppagebuilder' && $sppbContent) {
                        $this->importSppbData($db, (int) $existing->id, $sppbContent);
                    }
                    $result['updated']++;
                } else {
                    $moduleData['asset_id'] = 0;
                    $newId = $this->insertModule($db, $moduleData);
                    if ($newId) {
                        $this->updateMenuAssignments($db, $newId, $menuAssignments);
                        if ($moduleType === 'mod_sppagebuilder' && $sppbContent) {
                            $this->importSppbData($db, $newId, $sppbContent);
                        }
                        $result['imported']++;
                    } else {
                        $result['warnings'][] = sprintf(
                            'Failed to import module "%s" (%s).',
                            $moduleData['title'] ?? 'Unknown',
                            $moduleType
                        );
                        $result['skipped']++;
                    }
                }
            } catch (\Exception $e) {
                $result['warnings'][] = sprintf(
                    'Error importing module "%s": %s',
                    $moduleData['title'] ?? 'Unknown',
                    $e->getMessage()
                );
                $result['skipped']++;
            }
        }

        return $result;
    }

    protected function getInstalledModuleTypes(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('element'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('module'));

        $db->setQuery($query);
        return $db->loadColumn();
    }

    protected function findExistingModule(int $originalId, string $title, string $moduleType): ?object
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('title')])
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $originalId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $byId = $db->loadObject();

        if ($byId) {
            return $byId;
        }

        $query = $db->getQuery(true)
            ->select([$db->quoteName('id'), $db->quoteName('title')])
            ->from($db->quoteName('#__modules'))
            ->where([
                $db->quoteName('title') . ' = :title',
                $db->quoteName('module') . ' = :module',
            ])
            ->bind(':title', $title)
            ->bind(':module', $moduleType)
            ->setLimit(1);

        $db->setQuery($query);
        return $db->loadObject();
    }

    protected function insertModule(DatabaseInterface $db, array $data): ?int
    {
        $data = $this->filterColumns($db, '#__modules', $data);
        unset($data['id']);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }

        $obj->checked_out = 0;
        $obj->checked_out_time = null;

        if ($db->insertObject('#__modules', $obj, 'id')) {
            return (int) $obj->id;
        }

        return null;
    }

    protected function updateModule(DatabaseInterface $db, array $data): bool
    {
        $data = $this->filterColumns($db, '#__modules', $data);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }

        return $db->updateObject('#__modules', $obj, 'id');
    }

    protected function updateMenuAssignments(DatabaseInterface $db, int $moduleId, array $menuIds): void
    {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__modules_menu'))
            ->where($db->quoteName('moduleid') . ' = :moduleId')
            ->bind(':moduleId', $moduleId, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $db->execute();

        if (empty($menuIds)) {
            $obj = new \stdClass();
            $obj->moduleid = $moduleId;
            $obj->menuid = 0;
            $db->insertObject('#__modules_menu', $obj);
            return;
        }

        foreach ($menuIds as $menuId) {
            $obj = new \stdClass();
            $obj->moduleid = $moduleId;
            $obj->menuid = (int) $menuId;
            $db->insertObject('#__modules_menu', $obj);
        }
    }

    // --- SP Page Builder support ---

    protected function getSppbDataForModules(DatabaseInterface $db, array $moduleIds): array
    {
        $ext = 'mod_sppagebuilder';
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__sppagebuilder'))
            ->where($db->quoteName('extension') . ' = :ext')
            ->where($db->quoteName('extension_view') . ' = ' . $db->quote('module'))
            ->whereIn($db->quoteName('view_id'), $moduleIds)
            ->bind(':ext', $ext);

        $db->setQuery($query);
        $rows = $db->loadAssocList();

        foreach ($rows as &$row) {
            unset($row['checked_out'], $row['checked_out_time'], $row['asset_id']);
        }

        return $rows;
    }

    protected function importSppbData(DatabaseInterface $db, int $newModuleId, array $sppbRow): void
    {
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
            ->where($db->quoteName('view_id') . ' = :vid')
            ->bind(':vid', $newModuleId, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $existingId = $db->loadResult();

        $sppbRow['view_id'] = $newModuleId;

        $cols = [
            'title', 'text', 'extension', 'extension_view', 'view_id',
            'active', 'published', 'catid', 'access', 'ordering',
            'created_on', 'created_by', 'modified', 'modified_by',
            'attribs', 'og_title', 'og_image', 'og_description',
            'language', 'hits', 'css', 'content', 'version',
        ];

        if ($existingId) {
            $obj = new \stdClass();
            $obj->id = (int) $existingId;
            foreach ($cols as $col) {
                if (array_key_exists($col, $sppbRow)) {
                    $obj->$col = $sppbRow[$col];
                }
            }
            $db->updateObject('#__sppagebuilder', $obj, 'id');
        } else {
            $obj = new \stdClass();
            $obj->asset_id = 0;
            foreach ($cols as $col) {
                $obj->$col = $sppbRow[$col] ?? null;
            }
            $obj->checked_out = 0;
            $obj->checked_out_time = null;
            $db->insertObject('#__sppagebuilder', $obj);
        }
    }
}
