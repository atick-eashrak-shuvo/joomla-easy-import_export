<?php
defined('_JEXEC') or die;

$users        = $this->users;
$userGroups   = $this->userGroups;
$totalUsers   = count($users);
$activeUsers  = 0;
$blockedUsers = 0;

foreach ($users as $u) {
    if ((int) $u->block === 0) {
        $activeUsers++;
    } else {
        $blockedUsers++;
    }
}
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo $totalUsers; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_TOTAL_USERS'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $activeUsers; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_ACTIVE_USERS'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $blockedUsers; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_BLOCKED_USERS'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo count($userGroups); ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_USER_GROUPS'); ?></div></div>
</div>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchUser" class="input-medium" placeholder="<?php echo JText::_('COM_EASYIMPORTEXPORT_USER_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['user_search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmUserFilter({user_search:document.getElementById('mmSearchUser').value})">
            <i class="icon-search"></i>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="document.getElementById('userForm').submit()" id="btnExportUsers" disabled>
        <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?> <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=userexport.exportAll&' . $token . '=1'); ?>"><?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_USERS'); ?></a></li>
            <?php foreach ($userGroups as $ug): ?>
                <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=userexport.exportAll&filter_group=' . (int) $ug->id . '&' . $token . '=1'); ?>">
                    <?php echo htmlspecialchars($ug->title); ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <button type="button" class="btn btn-info" onclick="mmOpenModal('importUsersModal')">
        <i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="input-medium" onchange="mmUserFilter({filter_group:this.value})">
        <option value="0"><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_GROUPS'); ?></option>
        <?php foreach ($userGroups as $ug): ?>
            <option value="<?php echo (int) $ug->id; ?>" <?php echo $filters['filter_group'] === (int) $ug->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($ug->title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select class="input-medium" onchange="mmUserFilter({filter_block:this.value})">
        <option value="-1" <?php echo $filters['filter_block'] == -1 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="0" <?php echo $filters['filter_block'] === 0 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_ACTIVE_USERS'); ?></option>
        <option value="1" <?php echo $filters['filter_block'] === 1 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_BLOCKED_USERS'); ?></option>
    </select>
</div>

<form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=userexport.export'); ?>" method="post" id="userForm">
    <table class="table table-striped mm-table">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll2(this,'userForm','cid_users[]','btnExportUsers')" /></th>
                <th><?php echo JText::_('COM_EASYIMPORTEXPORT_USER_NAME'); ?></th>
                <th style="width:12%"><?php echo JText::_('COM_EASYIMPORTEXPORT_USERNAME'); ?></th>
                <th style="width:15%"><?php echo JText::_('COM_EASYIMPORTEXPORT_EMAIL'); ?></th>
                <th style="width:15%"><?php echo JText::_('COM_EASYIMPORTEXPORT_USER_GROUPS'); ?></th>
                <th style="width:7%"><?php echo JText::_('JSTATUS'); ?></th>
                <th style="width:10%"><?php echo JText::_('COM_EASYIMPORTEXPORT_REGISTERED'); ?></th>
                <th style="width:10%"><?php echo JText::_('COM_EASYIMPORTEXPORT_LAST_VISIT'); ?></th>
                <th style="width:4%"><?php echo JText::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="9" class="center"><div class="alert"><?php echo JText::_('COM_EASYIMPORTEXPORT_NO_USERS'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><input type="checkbox" name="cid_users[]" value="<?php echo (int) $u->id; ?>" onclick="mmToggleBtn('userForm','cid_users[]','btnExportUsers')" /></td>
                        <td><strong><?php echo htmlspecialchars($u->name); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($u->username); ?></code></td>
                        <td><small><?php echo htmlspecialchars($u->email); ?></small></td>
                        <td>
                            <?php foreach ($u->groups as $g): ?>
                                <span class="label"><?php echo htmlspecialchars($g); ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ((int) $u->block === 0): ?>
                                <span class="label label-success"><?php echo JText::_('COM_EASYIMPORTEXPORT_ACTIVE'); ?></span>
                            <?php else: ?>
                                <span class="label label-important"><?php echo JText::_('COM_EASYIMPORTEXPORT_BLOCKED'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars(isset($u->registerDate) ? $u->registerDate : ''); ?></small></td>
                        <td><small><?php echo htmlspecialchars($u->lastvisitDate ? $u->lastvisitDate : '—'); ?></small></td>
                        <td><?php echo (int) $u->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="userexport.export" />
    <?php echo JHtml::_('form.token'); ?>
</form>

<!-- Import Users Modal -->
<div class="modal hide fade" id="importUsersModal" tabindex="-1" role="dialog">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h3><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_USERS'); ?></h3>
    </div>
    <form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=userimport.import'); ?>" method="post" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="alert alert-block"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_USERS_NOTE'); ?></div>
            <div class="mm-import-zone" id="dzUsers">
                <div class="upload-icon"><i class="icon-upload"></i></div>
                <p><strong><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                <p class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                <input type="file" name="import_file_users" id="fiUsers" accept=".json" style="display:none" />
                <button type="button" class="btn" onclick="document.getElementById('fiUsers').click()">
                    <i class="icon-folder-open"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                </button>
                <div id="sfUsers" class="mm-selected-file"><span class="label label-info" id="snUsers"></span></div>
            </div>
            <div style="margin-top:15px">
                <label class="checkbox">
                    <input type="checkbox" name="import_overwrite_users" value="1" />
                    <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?>
                </label>
                <small class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC_USERS'); ?></small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
            <button type="submit" class="btn btn-primary" id="biUsers" disabled><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
        </div>
        <?php echo JHtml::_('form.token'); ?>
    </form>
</div>

<script>
(function() {
    mmSetupFileInput('fiUsers','sfUsers','snUsers','biUsers');
    mmSetupDropZone('dzUsers','fiUsers');
    var s = document.getElementById('mmSearchUser');
    if (s) s.addEventListener('keypress', function(e) { if (e.keyCode===13||e.key==='Enter'){e.preventDefault();mmUserFilter({user_search:this.value});} });
})();
</script>
