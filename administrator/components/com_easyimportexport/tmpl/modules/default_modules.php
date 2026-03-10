<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$items = $this->modules;
$totalCount = count($items);
$siteCount = $adminCount = $publishedCount = 0;
foreach ($items as $item) {
    if ((int)$item->client_id === 0) $siteCount++; else $adminCount++;
    if ((int)$item->published === 1) $publishedCount++;
}
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo $totalCount; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_TOTAL_MODULES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $siteCount; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_SITE_MODULES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $adminCount; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_ADMIN_MODULES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $publishedCount; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></div></div>
</div>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchMod" class="form-control" placeholder="<?php echo Text::_('COM_EASYIMPORTEXPORT_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmApplyFilter('modules',{search:document.getElementById('mmSearchMod').value})">
            <span class="icon-search" aria-hidden="true"></span>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="mmExportForm('moduleForm','export.export')" id="btnExportModules" disabled>
        <span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
            <span class="icon-download" aria-hidden="true"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=export.exportAll&client_id=-1&' . $token . '=1'); ?>"><?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_MODULES'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=export.exportAll&client_id=0&' . $token . '=1'); ?>"><?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_SITE_ONLY'); ?></a></li>
            <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=export.exportAll&client_id=1&' . $token . '=1'); ?>"><?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ADMIN_ONLY'); ?></a></li>
        </ul>
    </div>

    <button type="button" class="btn btn-info text-white" onclick="mmOpenModal('importModulesModal')">
        <span class="icon-upload" aria-hidden="true"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="form-select" style="width:auto" onchange="mmApplyFilter('modules',{client_id:this.value})">
        <option value="-1" <?php echo $filters['client_id']==-1?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_CLIENTS'); ?></option>
        <option value="0" <?php echo $filters['client_id']===0?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_SITE'); ?></option>
        <option value="1" <?php echo $filters['client_id']===1?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_ADMINISTRATOR'); ?></option>
    </select>
    <select class="form-select" style="width:auto" onchange="mmApplyFilter('modules',{filter_position:this.value})">
        <option value=""><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_POSITIONS'); ?></option>
        <?php foreach ($this->positions as $pos): ?>
            <option value="<?php echo htmlspecialchars($pos); ?>" <?php echo $filters['position']===$pos?'selected':''; ?>><?php echo htmlspecialchars($pos); ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" style="width:auto" onchange="mmApplyFilter('modules',{filter_state:this.value})">
        <option value="-3" <?php echo $filters['state']==-3?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="1" <?php echo $filters['state']===1?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></option>
        <option value="0" <?php echo $filters['state']===0?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_UNPUBLISHED'); ?></option>
        <option value="-2" <?php echo $filters['state']==-2?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_TRASHED'); ?></option>
    </select>
</div>

<form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=export.export'); ?>" method="post" name="adminForm" id="moduleForm">
    <table class="table mm-table table-striped">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="Joomla.checkAll(this, 'moduleForm')" /></th>
                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:10%"><?php echo Text::_('COM_EASYIMPORTEXPORT_TYPE'); ?></th>
                <th style="width:10%"><?php echo Text::_('COM_EASYIMPORTEXPORT_POSITION'); ?></th>
                <th style="width:7%"><?php echo Text::_('JSTATUS'); ?></th>
                <th style="width:7%"><?php echo Text::_('COM_EASYIMPORTEXPORT_CLIENT'); ?></th>
                <th style="width:7%"><?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="8" class="text-center"><div class="alert alert-info mb-0"><?php echo Text::_('COM_EASYIMPORTEXPORT_NO_MODULES'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($items as $i => $item):
                    $si = $stateLabels[(int)$item->published] ?? ['Unknown','secondary'];
                    $cl = (int)$item->client_id === 0 ? Text::_('COM_EASYIMPORTEXPORT_SITE') : Text::_('COM_EASYIMPORTEXPORT_ADMINISTRATOR');
                ?>
                    <tr>
                        <td><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'moduleForm', $item->title); ?></td>
                        <td><strong><?php echo htmlspecialchars($item->title); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($item->module); ?></code></td>
                        <td><?php echo htmlspecialchars($item->position ?: '—'); ?></td>
                        <td><span class="badge bg-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><span class="badge bg-<?php echo (int)$item->client_id===0?'primary':'dark'; ?>"><?php echo $cl; ?></span></td>
                        <td><?php echo htmlspecialchars($item->language); ?></td>
                        <td><?php echo (int)$item->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<!-- Import Modules Modal -->
<div class="modal fade mm-modal" id="importModulesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_MODULES'); ?></h5>
            <button type="button" class="btn-close" onclick="mmCloseModal('importModulesModal')"></button>
        </div>
        <form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=import.import'); ?>" method="post" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="mm-import-zone" id="dzModules">
                    <div class="upload-icon"><span class="icon-upload"></span></div>
                    <p class="mb-2"><strong><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                    <p class="text-muted mb-3"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                    <input type="file" name="import_file" id="fiModules" accept=".json" class="d-none" />
                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fiModules').click()">
                        <span class="icon-folder-open"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                    </button>
                    <div id="sfModules" class="mm-selected-file mt-2"><span class="badge bg-info p-2" id="snModules"></span></div>
                </div>
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="import_overwrite" value="1" id="owModules">
                        <label class="form-check-label" for="owModules"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?></label>
                    </div>
                    <small class="text-muted"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC'); ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="mmCloseModal('importModulesModal')"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="submit" class="btn btn-primary" id="biModules" disabled><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
            </div>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    </div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mf = document.getElementById('moduleForm');
    if (mf) mf.addEventListener('change', function() {
        document.getElementById('btnExportModules').disabled = mf.querySelectorAll('input[name="cid[]"]:checked').length === 0;
    });
    mmSetupFileInput('fiModules','sfModules','snModules','biModules');
    mmSetupDropZone('dzModules','fiModules');
    document.getElementById('mmSearchMod').addEventListener('keypress', function(e) {
        if (e.key==='Enter') { e.preventDefault(); mmApplyFilter('modules',{search:this.value}); }
    });
});
</script>
