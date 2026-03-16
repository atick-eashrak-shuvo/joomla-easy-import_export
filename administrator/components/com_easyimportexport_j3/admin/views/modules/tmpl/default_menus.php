<?php
defined('_JEXEC') or die;

$menuItems      = $this->menuItems;
$menuTypes      = $this->menuTypes;
$totalMenus     = count($menuItems);
$publishedMenus = 0;

foreach ($menuItems as $mi) {
    if ((int) $mi->published === 1) {
        $publishedMenus++;
    }
}
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo count($menuTypes); ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_MENU_TYPES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $totalMenus; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_MENU_ITEMS_TOTAL'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $publishedMenus; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></div></div>
</div>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchMenu" class="input-medium" placeholder="<?php echo JText::_('COM_EASYIMPORTEXPORT_MENU_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['menu_search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmMenuFilter({menu_search:document.getElementById('mmSearchMenu').value})">
            <i class="icon-search"></i>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="mmExportForm('menuForm','menuexport.export')" id="btnExportMenus" disabled>
        <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?> <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=menuexport.exportAll&' . $token . '=1'); ?>"><?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_MENUS'); ?></a></li>
            <?php foreach ($menuTypes as $mt): ?>
                <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=menuexport.exportAll&menutype=' . urlencode($mt->menutype) . '&' . $token . '=1'); ?>"><?php echo htmlspecialchars($mt->title); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <button type="button" class="btn btn-info" onclick="mmOpenModal('importMenusModal')">
        <i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="input-medium" onchange="mmMenuFilter({filter_menutype:this.value})">
        <option value=""><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_MENU_TYPES'); ?></option>
        <?php foreach ($menuTypes as $mt): ?>
            <option value="<?php echo htmlspecialchars($mt->menutype); ?>" <?php echo $filters['filter_menutype'] === $mt->menutype ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($mt->title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select class="input-medium" onchange="mmMenuFilter({menu_state:this.value})">
        <option value="-3" <?php echo $filters['menu_state'] == -3 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="1" <?php echo $filters['menu_state'] === 1 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></option>
        <option value="0" <?php echo $filters['menu_state'] === 0 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_UNPUBLISHED'); ?></option>
        <option value="-2" <?php echo $filters['menu_state'] === -2 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_TRASHED'); ?></option>
    </select>
</div>

<form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=menuexport.export'); ?>" method="post" id="menuForm">
    <table class="table table-striped mm-table">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll(this,'menuForm','cid[]')" /></th>
                <th><?php echo JText::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:10%"><?php echo JText::_('COM_EASYIMPORTEXPORT_MENU_TYPE_COL'); ?></th>
                <th style="width:12%"><?php echo JText::_('COM_EASYIMPORTEXPORT_LINK'); ?></th>
                <th style="width:7%"><?php echo JText::_('JSTATUS'); ?></th>
                <th style="width:5%"><?php echo JText::_('COM_EASYIMPORTEXPORT_HOME'); ?></th>
                <th style="width:7%"><?php echo JText::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo JText::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($menuItems)): ?>
                <tr><td colspan="8" class="center"><div class="alert"><?php echo JText::_('COM_EASYIMPORTEXPORT_NO_MENUS'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($menuItems as $i => $item):
                    $si     = isset($stateLabels[(int) $item->published]) ? $stateLabels[(int) $item->published] : array('Unknown', 'default');
                    $indent = str_repeat('<span class="mm-level-indent">—</span>', max(0, (int) $item->level - 1));
                ?>
                    <tr>
                        <td><input type="checkbox" name="cid[]" id="cb_m<?php echo $i; ?>" value="<?php echo (int) $item->id; ?>" onclick="mmToggleBtn('menuForm','cid[]','btnExportMenus')" /></td>
                        <td><?php echo $indent; ?> <strong><?php echo htmlspecialchars($item->title); ?></strong></td>
                        <td><span class="label"><?php echo htmlspecialchars($item->menutype); ?></span></td>
                        <td><small class="muted" style="word-break:break-all"><?php echo htmlspecialchars(substr($item->link, 0, 60)); ?></small></td>
                        <td><span class="label label-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><?php echo (int) $item->home ? '<i class="icon-star" style="color:#f0ad4e"></i>' : ''; ?></td>
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

<!-- Import Menus Modal -->
<div class="modal hide fade" id="importMenusModal" tabindex="-1" role="dialog">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h3><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_MENUS'); ?></h3>
    </div>
    <form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=menuimport.import'); ?>" method="post" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="mm-import-zone" id="dzMenus">
                <div class="upload-icon"><i class="icon-upload"></i></div>
                <p><strong><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                <p class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                <input type="file" name="import_file_menus" id="fiMenus" accept=".json" style="display:none" />
                <button type="button" class="btn" onclick="document.getElementById('fiMenus').click()">
                    <i class="icon-folder-open"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                </button>
                <div id="sfMenus" class="mm-selected-file"><span class="label label-info" id="snMenus"></span></div>
            </div>
            <div style="margin-top:15px">
                <label class="checkbox">
                    <input type="checkbox" name="import_overwrite_menus" value="1" />
                    <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?>
                </label>
                <small class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC_MENUS'); ?></small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
            <button type="submit" class="btn btn-primary" id="biMenus" disabled><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
        </div>
        <?php echo JHtml::_('form.token'); ?>
    </form>
</div>

<script>
(function() {
    var mf = document.getElementById('menuForm');
    if (mf) {
        mf.addEventListener('change', function() {
            document.getElementById('btnExportMenus').disabled = mf.querySelectorAll('input[name="cid[]"]:checked').length === 0;
        });
    }
    mmSetupFileInput('fiMenus','sfMenus','snMenus','biMenus');
    mmSetupDropZone('dzMenus','fiMenus');
    var s = document.getElementById('mmSearchMenu');
    if (s) s.addEventListener('keypress', function(e) { if (e.keyCode===13||e.key==='Enter'){e.preventDefault();mmMenuFilter({menu_search:this.value});} });
})();
</script>
