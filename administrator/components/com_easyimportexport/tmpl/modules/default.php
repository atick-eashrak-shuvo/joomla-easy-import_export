<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.modal', '.mm-modal', []);

$app       = Factory::getApplication();
$token     = Session::getFormToken();
$activeTab = $this->activeTab;
$filters   = $this->activeFilters;

$stateLabels = [
    1  => ['Published', 'success'],
    0  => ['Unpublished', 'secondary'],
    -1 => ['Disabled', 'warning'],
    -2 => ['Trashed', 'danger'],
];
?>

<style>
:root {
    --mm-bg-surface: #ffffff;
    --mm-bg-toolbar: var(--template-bg-dark-3, #f0f4fb);
    --mm-bg-dropzone: #f8fafc;
    --mm-bg-dropzone-hover: #eff6ff;
    --mm-text-primary: var(--template-text-dark, #1e293b);
    --mm-text-secondary: #64748b;
    --mm-border: #dee2e6;
    --mm-border-dashed: #cbd5e1;
    --mm-accent: #2563eb;
    --mm-accent-hover: #3b82f6;
    --mm-code-bg: rgba(0,0,0,.05);
}
:root[data-color-scheme="dark"],
[data-bs-theme="dark"] {
    --mm-bg-surface: #1e293b;
    --mm-bg-toolbar: rgba(255,255,255,.06);
    --mm-bg-dropzone: rgba(255,255,255,.04);
    --mm-bg-dropzone-hover: rgba(59,130,246,.12);
    --mm-text-primary: #e2e8f0;
    --mm-text-secondary: #94a3b8;
    --mm-border: #334155;
    --mm-border-dashed: #475569;
    --mm-accent: #60a5fa;
    --mm-accent-hover: #93c5fd;
    --mm-code-bg: rgba(255,255,255,.08);
}

.mm-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px; padding:14px; background:var(--mm-bg-toolbar); border-radius:8px; }
.mm-toolbar .btn { white-space:nowrap; }
.mm-stats { display:flex; gap:12px; margin-bottom:16px; }
.mm-stats .stat-card { flex:1; padding:14px 18px; border-radius:8px; background:var(--mm-bg-surface); border:1px solid var(--mm-border); }
.mm-stats .stat-card .stat-value { font-size:26px; font-weight:700; color:var(--mm-text-primary); }
.mm-stats .stat-card .stat-label { font-size:12px; color:var(--mm-text-secondary); margin-top:2px; }
.mm-search-group { display:flex; gap:6px; flex:1; min-width:180px; }
.mm-search-group input { flex:1; }
.mm-filter-bar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:12px; }
.mm-table th { white-space:nowrap; }
.mm-table code { background:var(--mm-code-bg); padding:2px 6px; border-radius:4px; }
.mm-import-zone { border:2px dashed var(--mm-border-dashed); border-radius:8px; padding:20px; text-align:center; background:var(--mm-bg-dropzone); transition:all .2s; }
.mm-import-zone.drag-over { border-color:var(--mm-accent-hover); background:var(--mm-bg-dropzone-hover); }
.mm-import-zone .upload-icon { font-size:40px; color:var(--mm-text-secondary); margin-bottom:6px; }
.mm-selected-file { display:none; margin-top:8px; }
.mm-selected-file.show { display:block; }
.mm-tabs { border-bottom:2px solid var(--mm-border); margin-bottom:20px; }
.mm-tabs .nav-link { font-weight:600; color:var(--mm-text-secondary); border:none; padding:10px 20px; }
.mm-tabs .nav-link:hover { color:var(--mm-text-primary); }
.mm-tabs .nav-link.active { color:var(--mm-accent); border-bottom:3px solid var(--mm-accent); background:transparent; }
.mm-tabs .nav-link .badge { font-size:11px; vertical-align:middle; margin-left:4px; }
.mm-level-indent { display:inline-block; width:20px; }
.modal-content { background:var(--mm-bg-surface); color:var(--mm-text-primary); }
[data-color-scheme="dark"] .modal-content,
[data-bs-theme="dark"] .modal-content { border-color:var(--mm-border); }
[data-color-scheme="dark"] .alert-secondary,
[data-bs-theme="dark"] .alert-secondary { background:rgba(255,255,255,.06); color:var(--mm-text-secondary); border-color:var(--mm-border); }
[data-color-scheme="dark"] .alert-warning,
[data-bs-theme="dark"] .alert-warning { background:rgba(245,158,11,.12); color:#fbbf24; border-color:rgba(245,158,11,.25); }
[data-color-scheme="dark"] .alert-info,
[data-bs-theme="dark"] .alert-info { background:rgba(59,130,246,.1); color:#93c5fd; border-color:rgba(59,130,246,.2); }
</style>

<div id="j-main-container" class="j-main-container">

    <!-- Tabs -->
    <ul class="nav nav-tabs mm-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'modules' ? 'active' : ''; ?>"
               href="<?php echo Route::_('index.php?option=com_easyimportexport&view=modules&tab=modules'); ?>">
                <span class="icon-cube" aria-hidden="true"></span>
                <?php echo Text::_('COM_EASYIMPORTEXPORT_TAB_MODULES'); ?>
                <span class="badge bg-primary"><?php echo count($this->modules); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'menus' ? 'active' : ''; ?>"
               href="<?php echo Route::_('index.php?option=com_easyimportexport&view=modules&tab=menus'); ?>">
                <span class="icon-menu" aria-hidden="true"></span>
                <?php echo Text::_('COM_EASYIMPORTEXPORT_TAB_MENUS'); ?>
                <span class="badge bg-info"><?php echo count($this->menuItems); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'articles' ? 'active' : ''; ?>"
               href="<?php echo Route::_('index.php?option=com_easyimportexport&view=modules&tab=articles'); ?>">
                <span class="icon-file-alt" aria-hidden="true"></span>
                <?php echo Text::_('COM_EASYIMPORTEXPORT_TAB_ARTICLES'); ?>
                <span class="badge bg-success"><?php echo count($this->articles); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $activeTab === 'users' ? 'active' : ''; ?>"
               href="<?php echo Route::_('index.php?option=com_easyimportexport&view=modules&tab=users'); ?>">
                <span class="icon-users" aria-hidden="true"></span>
                <?php echo Text::_('COM_EASYIMPORTEXPORT_TAB_USERS'); ?>
                <span class="badge bg-warning text-dark"><?php echo count($this->users); ?></span>
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <?php if ($activeTab === 'modules'): ?>
        <?php include __DIR__ . '/default_modules.php'; ?>
    <?php elseif ($activeTab === 'menus'): ?>
        <?php include __DIR__ . '/default_menus.php'; ?>
    <?php elseif ($activeTab === 'articles'): ?>
        <?php include __DIR__ . '/default_articles.php'; ?>
    <?php elseif ($activeTab === 'users'): ?>
        <?php include __DIR__ . '/default_users.php'; ?>
    <?php endif; ?>

</div>

<script>
function mmOpenModal(id) {
    var el = document.getElementById(id);
    if (el) { bootstrap.Modal.getOrCreateInstance(el).show(); }
}
function mmCloseModal(id) {
    var el = document.getElementById(id);
    if (el) { var m = bootstrap.Modal.getInstance(el); if(m) m.hide(); }
}
function formatFileSize(bytes) {
    if (bytes===0) return '0 B';
    var k=1024, s=['B','KB','MB'], i=Math.floor(Math.log(bytes)/Math.log(k));
    return parseFloat((bytes/Math.pow(k,i)).toFixed(1))+' '+s[i];
}
function mmSetupFileInput(inputId, displayId, nameId, btnId) {
    var fi = document.getElementById(inputId);
    if (!fi) return;
    fi.addEventListener('change', function() {
        var d = document.getElementById(displayId);
        var n = document.getElementById(nameId);
        var b = document.getElementById(btnId);
        if (this.files.length > 0) {
            n.textContent = this.files[0].name + ' (' + formatFileSize(this.files[0].size) + ')';
            d.classList.add('show');
            b.disabled = false;
        } else {
            d.classList.remove('show');
            b.disabled = true;
        }
    });
}
function mmSetupDropZone(zoneId, inputId) {
    var z = document.getElementById(zoneId);
    var fi = document.getElementById(inputId);
    if (!z || !fi) return;
    ['dragenter','dragover'].forEach(function(e) {
        z.addEventListener(e, function(ev) { ev.preventDefault(); ev.stopPropagation(); z.classList.add('drag-over'); });
    });
    ['dragleave','drop'].forEach(function(e) {
        z.addEventListener(e, function(ev) { ev.preventDefault(); ev.stopPropagation(); z.classList.remove('drag-over'); });
    });
    z.addEventListener('drop', function(e) {
        var files = e.dataTransfer.files;
        if (files.length > 0 && files[0].name.endsWith('.json')) {
            fi.files = files;
            fi.dispatchEvent(new Event('change'));
        }
    });
}
function mmApplyFilter(tab, params) {
    var url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    for (var k in params) url.searchParams.set(k, params[k]);
    window.location.href = url.toString();
}
function mmExportForm(formId, task) {
    var f = document.getElementById(formId);
    f.querySelector('[name=task]').value = task;
    f.submit();
}
function mmCheckAll(toggle, formId, name) {
    var f = document.getElementById(formId);
    var cbs = f.querySelectorAll('input[name="' + name + '"]');
    cbs.forEach(function(cb) { cb.checked = toggle.checked; });
    f.dispatchEvent(new Event('change'));
}
function mmCheckAll2(toggle, formId, name, btnId) {
    var f = document.getElementById(formId);
    f.querySelectorAll('input[name="'+name+'"]').forEach(function(cb) { cb.checked = toggle.checked; });
    document.getElementById(btnId).disabled = !toggle.checked;
}
function mmToggleBtn(formId, name, btnId) {
    var f = document.getElementById(formId);
    document.getElementById(btnId).disabled = f.querySelectorAll('input[name="'+name+'"]:checked').length === 0;
}
function mmMenuFilter(params) {
    var url = new URL(window.location.href);
    url.searchParams.set('tab', 'menus');
    for (var k in params) url.searchParams.set(k, params[k]);
    window.location.href = url.toString();
}
function mmArticleFilter(params) {
    var url = new URL(window.location.href);
    url.searchParams.set('tab', 'articles');
    for (var k in params) url.searchParams.set(k, params[k]);
    window.location.href = url.toString();
}
function mmExportCatForm() {
    document.getElementById('categoryForm').submit();
}
function mmExportArticleForm() {
    document.getElementById('articleForm').submit();
}
function mmUserFilter(params) {
    var url = new URL(window.location.href);
    url.searchParams.set('tab', 'users');
    for (var k in params) url.searchParams.set(k, params[k]);
    window.location.href = url.toString();
}
</script>
