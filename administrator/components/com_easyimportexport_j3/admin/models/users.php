<?php

defined('_JEXEC') or die;

jimport('joomla.application.component.model');

class EasyimportexportModelUsers extends JModelLegacy
{
    public function getAllUserIds()
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__users'));

        $db->setQuery($query);

        return (array) $db->loadColumn();
    }

    public function getExportData(array $userIds)
    {
        if (empty($userIds)) {
            return false;
        }

        $db = JFactory::getDbo();
        JArrayHelper::toInteger($userIds);
        $idsList = implode(',', $userIds);

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__users'))
            ->where($db->quoteName('id') . ' IN (' . $idsList . ')');

        $db->setQuery($query);
        $users = (array) $db->loadAssocList();

        if (empty($users)) {
            return false;
        }

        // Groups
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__user_usergroup_map'))
            ->where($db->quoteName('user_id') . ' IN (' . $idsList . ')');

        $db->setQuery($query);
        $groupRows = (array) $db->loadAssocList();

        $groupMap = array();
        foreach ($groupRows as $row) {
            $uid = (int) $row['user_id'];
            if (!isset($groupMap[$uid])) {
                $groupMap[$uid] = array();
            }
            $groupMap[$uid][] = (int) $row['group_id'];
        }

        // Profiles (if table exists)
        $profileRows = array();
        $profilesMap = array();
        $tables      = $db->getTableList();

        if (in_array($db->getPrefix() . 'user_profiles', $tables, true)) {
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__user_profiles'))
                ->where($db->quoteName('user_id') . ' IN (' . $idsList . ')');

            $db->setQuery($query);
            $profileRows = (array) $db->loadAssocList();

            foreach ($profileRows as $row) {
                $uid = (int) $row['user_id'];
                if (!isset($profilesMap[$uid])) {
                    $profilesMap[$uid] = array();
                }
                $profilesMap[$uid][] = array(
                    'profile_key'   => $row['profile_key'],
                    'profile_value' => $row['profile_value'],
                    'ordering'      => (int) $row['ordering'],
                );
            }
        }

        // Export groups definition
        $allGroupIds = array();
        foreach ($groupMap as $ids) {
            foreach ($ids as $gid) {
                $allGroupIds[] = $gid;
            }
        }
        $allGroupIds = array_unique($allGroupIds);

        $usergroups = array();

        if (!empty($allGroupIds)) {
            JArrayHelper::toInteger($allGroupIds);
            $idsList = implode(',', $allGroupIds);

            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__usergroups'))
                ->where($db->quoteName('id') . ' IN (' . $idsList . ')');

            $db->setQuery($query);
            $usergroups = (array) $db->loadAssocList();
        }

        $exportUsers = array();

        foreach ($users as $user) {
            $uid               = (int) $user['id'];
            $user['group_ids'] = isset($groupMap[$uid]) ? $groupMap[$uid] : array();
            $user['profiles']  = isset($profilesMap[$uid]) ? $profilesMap[$uid] : array();
            $exportUsers[]     = $user;
        }

        return array(
            'meta'       => array(
                'format_version' => '1.0',
                'type'           => 'users',
                'export_date'    => date('Y-m-d H:i:s'),
                'joomla_version' => JVERSION,
                'site_name'      => JFactory::getApplication()->getCfg('sitename', ''),
                'site_url'       => JUri::root(),
                'user_count'     => count($exportUsers),
            ),
            'usergroups' => $usergroups,
            'users'      => $exportUsers,
        );
    }

    public function importUsers(array $data, $overwrite = false)
    {
        $result = array(
            'success'  => true,
            'imported' => 0,
            'skipped'  => 0,
            'updated'  => 0,
            'error'    => '',
            'warnings' => array(),
        );

        $db    = JFactory::getDbo();
        $users = isset($data['users']) ? $data['users'] : array();

        if (empty($users)) {
            $result['success'] = false;
            $result['error']   = 'No users found in import file.';
            return $result;
        }

        $existingGroups = $this->getExistingGroupIds($db);

        $usergroups = isset($data['usergroups']) ? $data['usergroups'] : array();
        foreach ($usergroups as $ug) {
            $this->ensureUserGroup($db, $ug, $existingGroups);
        }

        foreach ($users as $userData) {
            try {
                $originalId = isset($userData['id']) ? (int) $userData['id'] : 0;
                $groupIds   = isset($userData['group_ids']) ? $userData['group_ids'] : array();
                $profiles   = isset($userData['profiles']) ? $userData['profiles'] : array();

                unset($userData['id'], $userData['group_ids'], $userData['profiles']);

                $existing = null;
                if ($overwrite) {
                    $existing = $this->findExistingUser($db, $originalId, isset($userData['username']) ? $userData['username'] : '', isset($userData['email']) ? $userData['email'] : '');
                } else {
                    $dup = $this->findExistingUser($db, 0, isset($userData['username']) ? $userData['username'] : '', isset($userData['email']) ? $userData['email'] : '');
                    if ($dup) {
                        $result['warnings'][] = sprintf(
                            'User "%s" (%s) already exists. Skipped.',
                            isset($userData['username']) ? $userData['username'] : '',
                            isset($userData['email']) ? $userData['email'] : ''
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
            } catch (Exception $e) {
                $uname = isset($userData['username']) ? $userData['username'] : 'Unknown';
                $result['warnings'][] = sprintf('Error importing user "%s": %s', $uname, $e->getMessage());
                $result['skipped']++;
            }
        }

        return $result;
    }

    protected function getExistingGroupIds($db)
    {
        $query = $db->getQuery(true)->select('id')->from($db->quoteName('#__usergroups'));
        $db->setQuery($query);
        return (array) $db->loadColumn();
    }

    protected function ensureUserGroup($db, array $ug, array &$existingIds)
    {
        $id = isset($ug['id']) ? (int) $ug['id'] : 0;
        if ($id > 0 && in_array($id, $existingIds)) {
            return;
        }

        $title = isset($ug['title']) ? $ug['title'] : '';
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__usergroups'))
            ->where($db->quoteName('title') . ' = ' . $db->quote($title));
        $db->setQuery($query, 0, 1);
        if ($db->loadResult()) {
            return;
        }

        $obj            = new stdClass();
        $obj->parent_id = isset($ug['parent_id']) ? (int) $ug['parent_id'] : 1;
        $obj->title     = $title;
        $obj->lft       = 0;
        $obj->rgt       = 0;
        $db->insertObject('#__usergroups', $obj, 'id');
        $existingIds[] = (int) $obj->id;
    }

    protected function findExistingUser($db, $id, $username, $email)
    {
        if ((int) $id > 0) {
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('id') . ' = ' . (int) $id);
            $db->setQuery($query);
            $obj = $db->loadObject();
            if ($obj) {
                return $obj;
            }
        }

        if (!empty($username)) {
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('username') . ' = ' . $db->quote($username));
            $db->setQuery($query, 0, 1);
            $obj = $db->loadObject();
            if ($obj) {
                return $obj;
            }
        }

        if (!empty($email)) {
            $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__users'))
                ->where($db->quoteName('email') . ' = ' . $db->quote($email));
            $db->setQuery($query, 0, 1);
            return $db->loadObject();
        }

        return null;
    }

    protected function insertUser($db, array $data)
    {
        $cols = array(
            'name', 'username', 'email', 'password', 'block', 'sendEmail',
            'registerDate', 'lastvisitDate', 'activation', 'params',
            'lastResetTime', 'resetCount', 'otpKey', 'otep', 'requireReset',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            $obj->$col = isset($data[$col]) ? $data[$col] : null;
        }

        if ($db->insertObject('#__users', $obj, 'id')) {
            return (int) $obj->id;
        }
        return null;
    }

    protected function updateUser($db, array $data)
    {
        $cols = array(
            'id', 'name', 'username', 'email', 'password', 'block', 'sendEmail',
            'registerDate', 'lastvisitDate', 'activation', 'params',
            'lastResetTime', 'resetCount', 'otpKey', 'otep', 'requireReset',
        );

        $obj = new stdClass();
        foreach ($cols as $col) {
            if (isset($data[$col])) {
                $obj->$col = $data[$col];
            }
        }

        return $db->updateObject('#__users', $obj, 'id');
    }

    protected function updateUserGroups($db, $userId, array $groupIds)
    {
        $userId = (int) $userId;

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__user_usergroup_map'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);
        $db->setQuery($query);
        $db->execute();

        if (empty($groupIds)) {
            $groupIds = array(2);
        }

        foreach ($groupIds as $gid) {
            $obj           = new stdClass();
            $obj->user_id  = $userId;
            $obj->group_id = (int) $gid;
            $db->insertObject('#__user_usergroup_map', $obj);
        }
    }

    protected function updateUserProfiles($db, $userId, array $profiles)
    {
        $userId = (int) $userId;
        $tables = $db->getTableList();

        if (!in_array($db->getPrefix() . 'user_profiles', $tables, true)) {
            return;
        }

        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__user_profiles'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);
        $db->setQuery($query);
        $db->execute();

        foreach ($profiles as $p) {
            $obj                = new stdClass();
            $obj->user_id       = $userId;
            $obj->profile_key   = isset($p['profile_key']) ? $p['profile_key'] : '';
            $obj->profile_value = isset($p['profile_value']) ? $p['profile_value'] : '';
            $obj->ordering      = isset($p['ordering']) ? (int) $p['ordering'] : 0;
            $db->insertObject('#__user_profiles', $obj);
        }
    }

    public function getUsers($search = '', $groupId = 0, $block = -1)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select(array(
                $db->quoteName('u.id'),
                $db->quoteName('u.name'),
                $db->quoteName('u.username'),
                $db->quoteName('u.email'),
                $db->quoteName('u.block'),
                $db->quoteName('u.registerDate'),
                $db->quoteName('u.lastvisitDate'),
            ))
            ->from($db->quoteName('#__users', 'u'))
            ->order($db->quoteName('u.name') . ' ASC');

        if (!empty($search)) {
            $wild = '%' . $db->escape($search, true) . '%';
            $query->where(
                '(' . $db->quoteName('u.name') . ' LIKE ' . $db->quote($wild)
                . ' OR ' . $db->quoteName('u.username') . ' LIKE ' . $db->quote($wild)
                . ' OR ' . $db->quoteName('u.email') . ' LIKE ' . $db->quote($wild) . ')'
            );
        }

        if ((int) $groupId > 0) {
            $query->join('INNER', $db->quoteName('#__user_usergroup_map', 'ug') . ' ON ' . $db->quoteName('ug.user_id') . ' = ' . $db->quoteName('u.id'))
                ->where($db->quoteName('ug.group_id') . ' = ' . (int) $groupId);
        }

        if ((int) $block >= 0) {
            $query->where($db->quoteName('u.block') . ' = ' . (int) $block);
        }

        $db->setQuery($query);
        $users = (array) $db->loadObjectList();

        $allIds = array();
        foreach ($users as $u) {
            $allIds[] = (int) $u->id;
        }

        $groupMap = array();
        if (!empty($allIds)) {
            $idsList = implode(',', $allIds);
            $gq = $db->getQuery(true)
                ->select(array($db->quoteName('m.user_id'), $db->quoteName('g.title')))
                ->from($db->quoteName('#__user_usergroup_map', 'm'))
                ->join('LEFT', $db->quoteName('#__usergroups', 'g') . ' ON ' . $db->quoteName('g.id') . ' = ' . $db->quoteName('m.group_id'))
                ->where($db->quoteName('m.user_id') . ' IN (' . $idsList . ')');

            $db->setQuery($gq);
            $rows = (array) $db->loadObjectList();

            foreach ($rows as $row) {
                $uid = (int) $row->user_id;
                if (!isset($groupMap[$uid])) {
                    $groupMap[$uid] = array();
                }
                $groupMap[$uid][] = $row->title;
            }
        }

        foreach ($users as $u) {
            $u->groups = isset($groupMap[(int) $u->id]) ? $groupMap[(int) $u->id] : array();
        }

        return $users;
    }

    public function getUserGroups()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__usergroups'))
            ->order($db->quoteName('lft') . ' ASC');

        $db->setQuery($query);

        return (array) $db->loadObjectList();
    }
}

