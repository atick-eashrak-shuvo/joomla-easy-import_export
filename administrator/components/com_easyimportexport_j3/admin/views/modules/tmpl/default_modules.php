<?php
defined('_JEXEC') or die;

$items      = $this->modules;
$totalCount = count($items);
$siteCount  = $adminCount = $publishedCount = 0;

foreach ($items as $item) {
    if ((int) $item->client_id === 0) {
        $siteCount++;
    } else {
        $adminCount++;
    }
    if ((int) $item->published === 1) {
        $publishedCount++;
    }
}
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo $totalCount; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_TOTAL_MODULES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $siteCount; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_SITE_MODULES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $adminCount; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_ADMIN_MODULES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $publishedCount; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></div></div>
</div>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchMod" class="input-medium" placeholder="<?php echo JText::_('COM_EASYIMPORTEXPORT_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmApplyFilter('modules',{search:document.getElementById('mmSearchMod').value})">
            <i class="icon-search"></i>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="mmExportForm('moduleForm','export.export')" id="btnExportModules" disabled>
        <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?> <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=export.exportAll&client_id=-1&' . $token . '=1'); ?>"><?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_MODULES'); ?></a></li>
            <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=export.exportAll&client_id=0&' . $token . '=1'); ?>"><?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_SITE_ONLY'); ?></a></li>
            <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=export.exportAll&client_id=1&' . $token . '=1'); ?>"><?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ADMIN_ONLY'); ?></a></li>
        </ul>
    </div>

    <button type="button" class="btn btn-info" onclick="mmOpenModal('importModulesModal')">
        <i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="input-medium" onchange="mmApplyFilter('modules',{client_id:this.value})">
        <option value="-1" <?php echo $filters['client_id'] == -1 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_CLIENTS'); ?></option>
        <option value="0" <?php echo $filters['client_id'] === 0 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_SITE'); ?></option>
        <option value="1" <?php echo $filters['client_id'] === 1 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_ADMINISTRATOR'); ?></option>
    </select>
    <select class="input-medium" onchange="mmApplyFilter('modules',{filter_position:this.value})">
        <option value=""><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_POSITIONS'); ?></option>
        <?php foreach ($this->positions as $pos): ?>
            <option value="<?php echo htmlspecialchars($pos); ?>" <?php echo $filters['position'] === $pos ? 'selected' : ''; ?>><?php echo htmlspecialchars($pos); ?></option>
        <?php endforeach; ?>
    </select>
    <select class="input-medium" onchange="mmApplyFilter('modules',{filter_state:this.value})">
        <option value="-3" <?php echo $filters['state'] == -3 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="1" <?php echo $filters['state'] === 1 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></option>
        <option value="0" <?php echo $filters['state'] === 0 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_UNPUBLISHED'); ?></option>
        <option value="-2" <?php echo $filters['state'] === -2 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_TRASHED'); ?></option>
    </select>
</div>

<form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=export.export'); ?>" method="post" name="adminForm" id="moduleForm">
    <table class="table table-striped mm-table">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll(this,'moduleForm','cid[]')" /></th>
                <th><?php echo JText::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:10%"><?php echo JText::_('COM_EASYIMPORTEXPORT_TYPE'); ?></th>
                <th style="width:10%"><?php echo JText::_('COM_EASYIMPORTEXPORT_POSITION'); ?></th>
                <th style="width:7%"><?php echo JText::_('JSTATUS'); ?></th>
                <th style="width:7%"><?php echo JText::_('COM_EASYIMPORTEXPORT_CLIENT'); ?></th>
                <th style="width:7%"><?php echo JText::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo JText::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="8" class="center"><div class="alert"><?php echo JText::_('COM_EASYIMPORTEXPORT_NO_MODULES'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($items as $i => $item):
                    $si = isset($stateLabels[(int) $item->published]) ? $stateLabels[(int) $item->published] : array('Unknown', 'default');
                    $cl = (int) $item->client_id === 0 ? JText::_('COM_EASYIMPORTEXPORT_SITE') : JText::_('COM_EASYIMPORTEXPORT_ADMINISTRATOR');
                ?>
                    <tr>
                        <td><input type="checkbox" name="cid[]" id="cb<?php echo $i; ?>" value="<?php echo (int) $item->id; ?>" onclick="mmToggleBtn('moduleForm','cid[]','btnExportModules')" /></td>
                        <td><strong><?php echo htmlspecialchars($item->title); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($item->module); ?></code></td>
                        <td><?php echo htmlspecialchars($item->position ? $item->position : '—'); ?></td>
                        <td><span class="label label-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><span class="label label-<?php echo (int) $item->client_id === 0 ? 'info' : 'inverse'; ?>"><?php echo $cl; ?></span></td>
                        <td><?php echo htmlspecialchars($item->language); ?></td>
                        <td><?php echo (int) $item->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="" />
    <?php echo JHtml::_('form.token'); ?>
</form>

<!-- Import Modules Modal -->
<div class="modal hide fade" id="importModulesModal" tabindex="-1" role="dialog">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h3><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_MODULES'); ?></h3>
    </div>
    <form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=import.import'); ?>" method="post" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="mm-import-zone" id="dzModules">
                <div class="upload-icon"><i class="icon-upload"></i></div>
                <p><strong><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                <p class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                <input type="file" name="import_file" id="fiModules" accept=".json" style="display:none" />
                <button type="button" class="btn" onclick="document.getElementById('fiModules').click()">
                    <i class="icon-folder-open"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                </button>
                <div id="sfModules" class="mm-selected-file"><span class="label label-info" id="snModules"></span></div>
            </div>
            <div style="margin-top:15px">
                <label class="checkbox">
                    <input type="checkbox" name="import_overwrite" value="1" />
                    <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?>
                </label>
                <small class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC'); ?></small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
            <button type="submit" class="btn btn-primary" id="biModules" disabled><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
        </div>
        <?php echo JHtml::_('form.token'); ?>
    </form>
</div>

<script>
(function() {
    var mf = document.getElementById('moduleForm');
    if (mf) {
        mf.addEventListener('change', function() {
            document.getElementById('btnExportModules').disabled = mf.querySelectorAll('input[name="cid[]"]:checked').length === 0;
        });
    }
    mmSetupFileInput('fiModules','sfModules','snModules','biModules');
    mmSetupDropZone('dzModules','fiModules');
    var searchMod = document.getElementById('mmSearchMod');
    if (searchMod) {
        searchMod.addEventListener('keypress', function(e) {
            if (e.keyCode === 13 || e.key === 'Enter') { e.preventDefault(); mmApplyFilter('modules',{search:this.value}); }
        });
    }
})();
</script>
