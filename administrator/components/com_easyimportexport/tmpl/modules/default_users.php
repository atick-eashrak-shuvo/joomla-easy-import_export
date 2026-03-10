<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$users = $this->users;
$userGroups = $this->userGroups;
$totalUsers = count($users);
$activeUsers = 0;
$blockedUsers = 0;
foreach ($users as $u) {
    if ((int)$u->block === 0) $activeUsers++; else $blockedUsers++;
}
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo $totalUsers; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_TOTAL_USERS'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $activeUsers; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_ACTIVE_USERS'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $blockedUsers; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_BLOCKED_USERS'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo count($userGroups); ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_USER_GROUPS'); ?></div></div>
</div>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchUser" class="form-control" placeholder="<?php echo Text::_('COM_EASYIMPORTEXPORT_USER_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['user_search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmUserFilter({user_search:document.getElementById('mmSearchUser').value})">
            <span class="icon-search"></span>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="document.getElementById('userForm').submit()" id="btnExportUsers" disabled>
        <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
            <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=userexport.exportAll&' . $token . '=1'); ?>"><?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_USERS'); ?></a></li>
            <?php foreach ($userGroups as $ug): ?>
                <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=userexport.exportAll&filter_group=' . (int)$ug->id . '&' . $token . '=1'); ?>">
                    <?php echo str_repeat('— ', max(0, (int)$ug->lft > 1 ? (int)ceil(((int)$ug->lft - 1) / 2) : 0)); ?><?php echo htmlspecialchars($ug->title); ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <button type="button" class="btn btn-info text-white" onclick="mmOpenModal('importUsersModal')">
        <span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="form-select" style="width:auto" onchange="mmUserFilter({filter_group:this.value})">
        <option value="0"><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_GROUPS'); ?></option>
        <?php foreach ($userGroups as $ug): ?>
            <option value="<?php echo (int)$ug->id; ?>" <?php echo $filters['filter_group']===(int)$ug->id?'selected':''; ?>>
                <?php echo htmlspecialchars($ug->title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" style="width:auto" onchange="mmUserFilter({filter_block:this.value})">
        <option value="-1" <?php echo $filters['filter_block']==-1?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="0" <?php echo $filters['filter_block']===0?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_ACTIVE_USERS'); ?></option>
        <option value="1" <?php echo $filters['filter_block']===1?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_BLOCKED_USERS'); ?></option>
    </select>
</div>

<form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=userexport.export'); ?>" method="post" id="userForm">
    <table class="table mm-table table-striped">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll2(this,'userForm','cid_users[]','btnExportUsers')" /></th>
                <th><?php echo Text::_('COM_EASYIMPORTEXPORT_USER_NAME'); ?></th>
                <th style="width:12%"><?php echo Text::_('COM_EASYIMPORTEXPORT_USERNAME'); ?></th>
                <th style="width:15%"><?php echo Text::_('COM_EASYIMPORTEXPORT_EMAIL'); ?></th>
                <th style="width:15%"><?php echo Text::_('COM_EASYIMPORTEXPORT_USER_GROUPS'); ?></th>
                <th style="width:7%"><?php echo Text::_('JSTATUS'); ?></th>
                <th style="width:10%"><?php echo Text::_('COM_EASYIMPORTEXPORT_REGISTERED'); ?></th>
                <th style="width:10%"><?php echo Text::_('COM_EASYIMPORTEXPORT_LAST_VISIT'); ?></th>
                <th style="width:4%"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="9" class="text-center"><div class="alert alert-info mb-0"><?php echo Text::_('COM_EASYIMPORTEXPORT_NO_USERS'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><input type="checkbox" name="cid_users[]" value="<?php echo (int)$u->id; ?>" onclick="mmToggleBtn('userForm','cid_users[]','btnExportUsers')" /></td>
                        <td><strong><?php echo htmlspecialchars($u->name); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($u->username); ?></code></td>
                        <td><small><?php echo htmlspecialchars($u->email); ?></small></td>
                        <td>
                            <?php foreach ($u->groups as $g): ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($g); ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ((int)$u->block === 0): ?>
                                <span class="badge bg-success"><?php echo Text::_('COM_EASYIMPORTEXPORT_ACTIVE'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger"><?php echo Text::_('COM_EASYIMPORTEXPORT_BLOCKED'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo htmlspecialchars($u->registerDate ?? ''); ?></small></td>
                        <td><small><?php echo htmlspecialchars($u->lastvisitDate ?: '—'); ?></small></td>
                        <td><?php echo (int)$u->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="userexport.export" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<!-- Import Users Modal -->
<div class="modal fade mm-modal" id="importUsersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_USERS'); ?></h5>
            <button type="button" class="btn-close" onclick="mmCloseModal('importUsersModal')"></button>
        </div>
        <form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=userimport.import'); ?>" method="post" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="alert alert-warning">
                    <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_USERS_NOTE'); ?>
                </div>
                <div class="mm-import-zone" id="dzUsers">
                    <div class="upload-icon"><span class="icon-upload"></span></div>
                    <p class="mb-2"><strong><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                    <p class="text-muted mb-3"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                    <input type="file" name="import_file_users" id="fiUsers" accept=".json" class="d-none" />
                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fiUsers').click()">
                        <span class="icon-folder-open"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                    </button>
                    <div id="sfUsers" class="mm-selected-file mt-2"><span class="badge bg-info p-2" id="snUsers"></span></div>
                </div>
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="import_overwrite_users" value="1" id="owUsers">
                        <label class="form-check-label" for="owUsers"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?></label>
                    </div>
                    <small class="text-muted"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC_USERS'); ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="mmCloseModal('importUsersModal')"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="submit" class="btn btn-primary" id="biUsers" disabled><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
            </div>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    </div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    mmSetupFileInput('fiUsers','sfUsers','snUsers','biUsers');
    mmSetupDropZone('dzUsers','fiUsers');
    document.getElementById('mmSearchUser').addEventListener('keypress', function(e) {
        if (e.key==='Enter') { e.preventDefault(); mmUserFilter({user_search:this.value}); }
    });
});
</script>
