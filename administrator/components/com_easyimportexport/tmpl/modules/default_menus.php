<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$menuItems = $this->menuItems;
$menuTypes = $this->menuTypes;
$totalMenus = count($menuItems);
$publishedMenus = 0;
foreach ($menuItems as $mi) { if ((int)$mi->published === 1) $publishedMenus++; }
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo count($menuTypes); ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_MENU_TYPES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $totalMenus; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_MENU_ITEMS_TOTAL'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $publishedMenus; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></div></div>
</div>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchMenu" class="form-control" placeholder="<?php echo Text::_('COM_EASYIMPORTEXPORT_MENU_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['menu_search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmMenuFilter({menu_search:document.getElementById('mmSearchMenu').value})">
            <span class="icon-search"></span>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="mmExportForm('menuForm','menuexport.export')" id="btnExportMenus" disabled>
        <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
            <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=menuexport.exportAll&' . $token . '=1'); ?>"><?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_MENUS'); ?></a></li>
            <?php foreach ($menuTypes as $mt): ?>
                <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=menuexport.exportAll&menutype=' . urlencode($mt->menutype) . '&' . $token . '=1'); ?>"><?php echo htmlspecialchars($mt->title); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <button type="button" class="btn btn-info text-white" onclick="mmOpenModal('importMenusModal')">
        <span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="form-select" style="width:auto" onchange="mmMenuFilter({filter_menutype:this.value})">
        <option value=""><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_MENU_TYPES'); ?></option>
        <?php foreach ($menuTypes as $mt): ?>
            <option value="<?php echo htmlspecialchars($mt->menutype); ?>" <?php echo $filters['filter_menutype']===$mt->menutype?'selected':''; ?>>
                <?php echo htmlspecialchars($mt->title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" style="width:auto" onchange="mmMenuFilter({menu_state:this.value})">
        <option value="-3" <?php echo $filters['menu_state']==-3?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="1" <?php echo $filters['menu_state']===1?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></option>
        <option value="0" <?php echo $filters['menu_state']===0?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_UNPUBLISHED'); ?></option>
        <option value="-2" <?php echo $filters['menu_state']==-2?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_TRASHED'); ?></option>
    </select>
</div>

<form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=menuexport.export'); ?>" method="post" id="menuForm">
    <table class="table mm-table table-striped">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll(this,'menuForm','cid[]')" /></th>
                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:10%"><?php echo Text::_('COM_EASYIMPORTEXPORT_MENU_TYPE_COL'); ?></th>
                <th style="width:12%"><?php echo Text::_('COM_EASYIMPORTEXPORT_LINK'); ?></th>
                <th style="width:7%"><?php echo Text::_('JSTATUS'); ?></th>
                <th style="width:5%"><?php echo Text::_('COM_EASYIMPORTEXPORT_HOME'); ?></th>
                <th style="width:7%"><?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($menuItems)): ?>
                <tr><td colspan="8" class="text-center"><div class="alert alert-info mb-0"><?php echo Text::_('COM_EASYIMPORTEXPORT_NO_MENUS'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($menuItems as $i => $item):
                    $si = $stateLabels[(int)$item->published] ?? ['Unknown','secondary'];
                    $indent = str_repeat('<span class="mm-level-indent">—</span>', max(0, (int)$item->level - 1));
                ?>
                    <tr>
                        <td><?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'menuForm', $item->title); ?></td>
                        <td><?php echo $indent; ?> <strong><?php echo htmlspecialchars($item->title); ?></strong></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item->menutype); ?></span></td>
                        <td><small class="text-muted" style="word-break:break-all"><?php echo htmlspecialchars(substr($item->link, 0, 60)); ?></small></td>
                        <td><span class="badge bg-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><?php echo (int)$item->home ? '<span class="icon-star text-warning"></span>' : ''; ?></td>
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

<!-- Import Menus Modal -->
<div class="modal fade mm-modal" id="importMenusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_MENUS'); ?></h5>
            <button type="button" class="btn-close" onclick="mmCloseModal('importMenusModal')"></button>
        </div>
        <form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=menuimport.import'); ?>" method="post" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="mm-import-zone" id="dzMenus">
                    <div class="upload-icon"><span class="icon-upload"></span></div>
                    <p class="mb-2"><strong><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                    <p class="text-muted mb-3"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                    <input type="file" name="import_file_menus" id="fiMenus" accept=".json" class="d-none" />
                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fiMenus').click()">
                        <span class="icon-folder-open"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                    </button>
                    <div id="sfMenus" class="mm-selected-file mt-2"><span class="badge bg-info p-2" id="snMenus"></span></div>
                </div>
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="import_overwrite_menus" value="1" id="owMenus">
                        <label class="form-check-label" for="owMenus"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?></label>
                    </div>
                    <small class="text-muted"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC_MENUS'); ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="mmCloseModal('importMenusModal')"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="submit" class="btn btn-primary" id="biMenus" disabled><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
            </div>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    </div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mf = document.getElementById('menuForm');
    if (mf) mf.addEventListener('change', function() {
        document.getElementById('btnExportMenus').disabled = mf.querySelectorAll('input[name="cid[]"]:checked').length === 0;
    });
    mmSetupFileInput('fiMenus','sfMenus','snMenus','biMenus');
    mmSetupDropZone('dzMenus','fiMenus');
    document.getElementById('mmSearchMenu').addEventListener('keypress', function(e) {
        if (e.key==='Enter') { e.preventDefault(); mmMenuFilter({menu_search:this.value}); }
    });
});
</script>
