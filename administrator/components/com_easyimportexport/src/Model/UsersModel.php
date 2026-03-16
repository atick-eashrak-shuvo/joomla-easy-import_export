<?php

namespace Joomla\Component\Easyimportexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

class UsersModel extends BaseDatabaseModel
{
    protected function filterColumns(DatabaseInterface $db, string $table, array $data): array
    {
        static $columnCache = [];
        if (!isset($columnCache[$table])) {
            $columnCache[$table] = array_keys($db->getTableColumns($table));
        }
        return array_intersect_key($data, array_flip($columnCache[$table]));
    }

    public function getUsers(string $search = '', int $groupId = 0, int $block = -1): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('u.id'),
                $db->quoteName('u.name'),
                $db->quoteName('u.username'),
                $db->quoteName('u.email'),
                $db->quoteName('u.block'),
                $db->quoteName('u.registerDate'),
                $db->quoteName('u.lastvisitDate'),
            ])
            ->from($db->quoteName('#__users', 'u'))
            ->order($db->quoteName('u.name') . ' ASC');

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('u.name') . ' LIKE :s1'
                . ' OR ' . $db->quoteName('u.username') . ' LIKE :s2'
                . ' OR ' . $db->quoteName('u.email') . ' LIKE :s3)'
            )
            ->bind(':s1', $wild)->bind(':s2', $wild)->bind(':s3', $wild);
        }

        if ($groupId > 0) {
            $query->join('INNER', $db->quoteName('#__user_usergroup_map', 'ug'),
                $db->quoteName('ug.user_id') . ' = ' . $db->quoteName('u.id'))
                ->where($db->quoteName('ug.group_id') . ' = :gid')
                ->bind(':gid', $groupId, \Joomla\Database\ParameterType::INTEGER);
        }

        if ($block >= 0) {
            $query->where($db->quoteName('u.block') . ' = :block')
                ->bind(':block', $block, \Joomla\Database\ParameterType::INTEGER);
        }

        $db->setQuery($query);
        $users = $db->loadObjectList();

        $allIds = array_column($users, 'id');
        $groupMap = [];
        if (!empty($allIds)) {
            $gq = $db->getQuery(true)
                ->select([$db->quoteName('m.user_id'), $db->quoteName('g.title')])
                ->from($db->quoteName('#__user_usergroup_map', 'm'))
                ->join('LEFT', $db->quoteName('#__usergroups', 'g'), $db->quoteName('g.id') . ' = ' . $db->quoteName('m.group_id'))
                ->whereIn($db->quoteName('m.user_id'), array_map('intval', $allIds));
            $db->setQuery($gq);
            foreach ($db->loadObjectList() as $row) {
                $groupMap[(int) $row->user_id][] = $row->title;
            }
        }

        foreach ($users as &$u) {
            $u->groups = $groupMap[(int) $u->id] ?? [];
        }

        return $users;
    }

    public function getUserGroups(): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__usergroups'))
            ->order($db->quoteName('lft') . ' ASC');
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    public function getAllUserIds(int $groupId = 0): array
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__users'));

        if ($groupId > 0) {
            $query->join('INNER', $db->quoteName('#__user_usergroup_map', 'ug'),
                $db->quoteName('ug.user_id') . ' = ' . $db->quoteName('id'))
                ->where($db->quoteName('ug.group_id') . ' = :gid')
                ->bind(':gid', $groupId, \Joomla\Database\ParameterType::INTEGER);
        }

        $db->setQuery($query);
        return $db->loadColumn();
    }

    public function getExportData(array $userIds): array|false
    {
        if (empty($userIds)) {
            return false;
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__users'))
            ->whereIn($db->quoteName('id'), $userIds);
        $db->setQuery($query);
        $users = $db->loadAssocList();

        if (empty($users)) {
            return false;
        }

        $gq = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__user_usergroup_map'))
            ->whereIn($db->quoteName('user_id'), $userIds);
        $db->setQuery($gq);
        $groupMapRows = $db->loadAssocList();

        $groupMap = [];
        foreach ($groupMapRows as $row) {
            $groupMap[(int) $row['user_id']][] = (int) $row['group_id'];
        }

        $pq = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__user_profiles'))
            ->whereIn($db->quoteName('user_id'), $userIds);
        $db->setQuery($pq);
        $profileRows = $db->loadAssocList();

        $profileMap = [];
        foreach ($profileRows as $row) {
            $profileMap[(int) $row['user_id']][] = [
                'profile_key'   => $row['profile_key'],
                'profile_value' => $row['profile_value'],
                'ordering'      => (int) $row['ordering'],
            ];
        }

        $allGroupIds = array_unique(array_merge(...array_values($groupMap ?: [[]])));
        $usergroups = [];
        if (!empty($allGroupIds)) {
            $ugq = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__usergroups'))
                ->whereIn($db->quoteName('id'), $allGroupIds);
            $db->setQuery($ugq);
            $usergroups = $db->loadAssocList();
        }

        $exportUsers = [];
        foreach ($users as $user) {
            $uid = (int) $user['id'];
            $user['group_ids'] = $groupMap[$uid] ?? [];
            $user['profiles']  = $profileMap[$uid] ?? [];
            $exportUsers[] = $user;
        }

        return [
            'meta' => [
                'format_version' => '1.0',
                'type'           => 'users',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => Factory::getApplication()->get('sitename', ''),
                'site_url'       => (string) \Joomla\CMS\Uri\Uri::root(),
                'user_count'     => count($exportUsers),
            ],
            'usergroups' => $usergroups,
            'users'      => $exportUsers,
        ];
    }

    public function importUsers(array $data, bool $overwrite = false): array
    {
        $result = [
            'success' => true, 'imported' => 0, 'skipped' => 0,
            'updated' => 0, 'error' => '', 'warnings' => [],
        ];

        $db = $this->getDatabase();
        $users = $data['users'] ?? [];

        if (empty($users)) {
            $result['success'] = false;
            $result['error'] = 'No users found in import file.';
            return $result;
        }

        $existingGroups = $this->getExistingGroupIds($db);

        foreach ($data['usergroups'] ?? [] as $ug) {
            $this->ensureUserGroup($db, $ug, $existingGroups);
        }

        foreach ($users as $userData) {
            try {
                $originalId = (int) ($userData['id'] ?? 0);
                $groupIds   = $userData['group_ids'] ?? [];
                $profiles   = $userData['profiles'] ?? [];

                unset($userData['id'], $userData['group_ids'], $userData['profiles']);

                $existing = null;
                if ($overwrite) {
                    $existing = $this->findExistingUser($db, $originalId, $userData['username'] ?? '', $userData['email'] ?? '');
                } else {
                    $dup = $this->findExistingUser($db, 0, $userData['username'] ?? '', $userData['email'] ?? '');
                    if ($dup) {
                        $result['warnings'][] = sprintf(
                            'User "%s" (%s) already exists. Skipped.',
                            $userData['username'] ?? '', $userData['email'] ?? ''
                        );
                        $result['skipped']++;
                        continue;
                    }
                }

                if ($existing) {
                    $userData['id'] = (int) $existing->id;
                    $this->updateUser($db, $userData);
                    $this->updateUserGroups($db, (int) $existing->id, $groupIds);
                    $this->updateUserProfiles($db, (int) $existing->id, $profiles);
                    $result['updated']++;
                } else {
                    $newId = $this->insertUser($db, $userData);
                    if ($newId) {
                        $this->updateUserGroups($db, $newId, $groupIds);
                        $this->updateUserProfiles($db, $newId, $profiles);
                        $result['imported']++;
                    } else {
                        $result['skipped']++;
                    }
                }
            } catch (\Exception $e) {
                $result['warnings'][] = sprintf('Error importing user "%s": %s', $userData['username'] ?? 'Unknown', $e->getMessage());
                $result['skipped']++;
            }
        }

        return $result;
    }

    protected function getExistingGroupIds(DatabaseInterface $db): array
    {
        $query = $db->getQuery(true)->select('id')->from($db->quoteName('#__usergroups'));
        $db->setQuery($query);
        return $db->loadColumn();
    }

    protected function ensureUserGroup(DatabaseInterface $db, array $ug, array &$existingIds): void
    {
        $id = (int) ($ug['id'] ?? 0);
        if ($id > 0 && in_array($id, $existingIds)) {
            return;
        }

        $title = $ug['title'] ?? '';
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = :t')
            ->bind(':t', $title)
            ->setLimit(1);
        $db->setQuery($query);
        if ($db->loadResult()) {
            return;
        }

        $obj = new \stdClass();
        $obj->parent_id = (int) ($ug['parent_id'] ?? 1);
        $obj->title = $title;
        $obj->lft = 0;
        $obj->rgt = 0;
        $db->insertObject('#__usergroups', $obj, 'id');
        $existingIds[] = (int) $obj->id;
    }

    protected function findExistingUser(DatabaseInterface $db, int $id, string $username, string $email): ?object
    {
        if ($id > 0) {
            $query = $db->getQuery(true)
                ->select('id')->from($db->quoteName('#__users'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);
            $db->setQuery($query);
            $obj = $db->loadObject();
            if ($obj) {
                return $obj;
            }
        }

        if (!empty($username)) {
            $query = $db->getQuery(true)
                ->select('id')->from($db->quoteName('#__users'))
                ->where($db->quoteName('username') . ' = :u')
                ->bind(':u', $username)->setLimit(1);
            $db->setQuery($query);
            $obj = $db->loadObject();
            if ($obj) {
                return $obj;
            }
        }

        if (!empty($email)) {
            $query = $db->getQuery(true)
                ->select('id')->from($db->quoteName('#__users'))
                ->where($db->quoteName('email') . ' = :e')
                ->bind(':e', $email)->setLimit(1);
            $db->setQuery($query);
            return $db->loadObject();
        }

        return null;
    }

    protected function hasTableColumn(DatabaseInterface $db, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;

        if (!isset($cache[$key])) {
            $columns = $db->getTableColumns($table);
            $cache[$key] = isset($columns[$column]);
        }

        return $cache[$key];
    }

    protected function insertUser(DatabaseInterface $db, array $data): ?int
    {
        $data = $this->filterColumns($db, '#__users', $data);
        unset($data['id']);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }

        if ($db->insertObject('#__users', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateUser(DatabaseInterface $db, array $data): bool
    {
        $data = $this->filterColumns($db, '#__users', $data);

        $obj = new \stdClass();
        foreach ($data as $col => $val) {
            $obj->$col = $val;
        }

        return $db->updateObject('#__users', $obj, 'id');
    }

    protected function updateUserGroups(DatabaseInterface $db, int $userId, array $groupIds): void
    {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__user_usergroup_map'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->bind(':uid', $userId, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $db->execute();

        if (empty($groupIds)) {
            $groupIds = [2];
        }

        foreach ($groupIds as $gid) {
            $obj = new \stdClass();
            $obj->user_id = $userId;
            $obj->group_id = (int) $gid;
            $db->insertObject('#__user_usergroup_map', $obj);
        }
    }

    protected function updateUserProfiles(DatabaseInterface $db, int $userId, array $profiles): void
    {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__user_profiles'))
            ->where($db->quoteName('user_id') . ' = :uid')
            ->bind(':uid', $userId, \Joomla\Database\ParameterType::INTEGER);
        $db->setQuery($query);
        $db->execute();

        foreach ($profiles as $p) {
            $obj = new \stdClass();
            $obj->user_id = $userId;
            $obj->profile_key = $p['profile_key'] ?? '';
            $obj->profile_value = $p['profile_value'] ?? '';
            $obj->ordering = (int) ($p['ordering'] ?? 0);
            $db->insertObject('#__user_profiles', $obj);
        }
    }
}
