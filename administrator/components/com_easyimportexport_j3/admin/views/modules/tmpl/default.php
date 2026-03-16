<?php

defined('_JEXEC') or die;

$token     = JSession::getFormToken();
$activeTab = $this->activeTab;
$filters   = $this->activeFilters;

$stateLabels = array(
    1  => array('Published', 'success'),
    0  => array('Unpublished', 'default'),
    -1 => array('Disabled', 'warning'),
    -2 => array('Trashed', 'important'),
);

JHtml::_('bootstrap.tooltip');
?>

<style>
.mm-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px; padding:14px; background:#f5f5f5; border-radius:4px; border:1px solid #ddd; }
.mm-toolbar .btn { white-space:nowrap; }
.mm-stats { display:flex; gap:12px; margin-bottom:16px; }
.mm-stats .stat-card { flex:1; padding:14px 18px; border-radius:4px; background:#fff; border:1px solid #ddd; }
.mm-stats .stat-card .stat-value { font-size:26px; font-weight:700; color:#333; }
.mm-stats .stat-card .stat-label { font-size:12px; color:#999; margin-top:2px; }
.mm-search-group { display:flex; gap:6px; flex:1; min-width:180px; }
.mm-search-group input { flex:1; }
.mm-filter-bar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:12px; }
.mm-table th { white-space:nowrap; }
.mm-table code { background:rgba(0,0,0,.05); padding:2px 6px; border-radius:4px; }
.mm-import-zone { border:2px dashed #ccc; border-radius:4px; padding:20px; text-align:center; background:#fafafa; transition:all .2s; }
.mm-import-zone .upload-icon { font-size:40px; color:#999; margin-bottom:6px; }
.mm-selected-file { display:none; margin-top:8px; }
.mm-selected-file.show { display:block; }
.mm-level-indent { display:inline-block; width:20px; }
.mm-tabs { list-style:none; padding:0; margin:0 0 20px; border-bottom:2px solid #ddd; display:flex; }
.mm-tabs li { margin:0; }
.mm-tabs li a { display:block; font-weight:600; color:#999; text-decoration:none; padding:10px 20px; border-bottom:3px solid transparent; }
.mm-tabs li a:hover { color:#333; text-decoration:none; }
.mm-tabs li a.active { color:#2563eb; border-bottom-color:#2563eb; }
.mm-tabs li a .badge { font-size:11px; vertical-align:middle; margin-left:4px; }
</style>

<div id="j-main-container">

    <ul class="mm-tabs">
        <li>
            <a class="<?php echo $activeTab === 'modules' ? 'active' : ''; ?>"
               href="<?php echo JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=modules'); ?>">
                <?php echo JText::_('COM_EASYIMPORTEXPORT_TAB_MODULES'); ?>
                <span class="badge"><?php echo count($this->modules); ?></span>
            </a>
        </li>
        <li>
            <a class="<?php echo $activeTab === 'menus' ? 'active' : ''; ?>"
               href="<?php echo JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=menus'); ?>">
                <?php echo JText::_('COM_EASYIMPORTEXPORT_TAB_MENUS'); ?>
                <span class="badge badge-info"><?php echo count($this->menuItems); ?></span>
            </a>
        </li>
        <li>
            <a class="<?php echo $activeTab === 'articles' ? 'active' : ''; ?>"
               href="<?php echo JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=articles'); ?>">
                <?php echo JText::_('COM_EASYIMPORTEXPORT_TAB_ARTICLES'); ?>
                <span class="badge badge-success"><?php echo count($this->articles); ?></span>
            </a>
        </li>
        <li>
            <a class="<?php echo $activeTab === 'users' ? 'active' : ''; ?>"
               href="<?php echo JRoute::_('index.php?option=com_easyimportexport&view=modules&tab=users'); ?>">
                <?php echo JText::_('COM_EASYIMPORTEXPORT_TAB_USERS'); ?>
                <span class="badge badge-warning"><?php echo count($this->users); ?></span>
            </a>
        </li>
    </ul>

    <script>
    function mmOpenModal(id) {
        var el = document.getElementById(id);
        if (el && typeof jQuery !== 'undefined') { jQuery('#' + id).modal('show'); }
    }
    function mmCloseModal(id) {
        if (typeof jQuery !== 'undefined') { jQuery('#' + id).modal('hide'); }
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
                d.className = 'mm-selected-file show';
                b.disabled = false;
            } else {
                d.className = 'mm-selected-file';
                b.disabled = true;
            }
        });
    }
    function mmSetupDropZone(zoneId, inputId) {
        var z = document.getElementById(zoneId);
        var fi = document.getElementById(inputId);
        if (!z || !fi) return;
        z.addEventListener('dragenter', function(ev) { ev.preventDefault(); ev.stopPropagation(); z.style.borderColor = '#2563eb'; z.style.background = '#eff6ff'; });
        z.addEventListener('dragover', function(ev) { ev.preventDefault(); ev.stopPropagation(); });
        z.addEventListener('dragleave', function(ev) { ev.preventDefault(); ev.stopPropagation(); z.style.borderColor = '#ccc'; z.style.background = '#fafafa'; });
        z.addEventListener('drop', function(ev) {
            ev.preventDefault(); ev.stopPropagation();
            z.style.borderColor = '#ccc'; z.style.background = '#fafafa';
            var files = ev.dataTransfer.files;
            if (files.length > 0 && files[0].name.match(/\.json$/i)) {
                fi.files = files;
                var evt = document.createEvent('HTMLEvents');
                evt.initEvent('change', true, false);
                fi.dispatchEvent(evt);
            }
        });
    }
    function mmApplyFilter(tab, params) {
        var base = window.location.href.split('?')[0];
        var search = new URLSearchParams(window.location.search);
        search.set('tab', tab);
        for (var k in params) { if (params.hasOwnProperty(k)) search.set(k, params[k]); }
        window.location.href = base + '?' + search.toString();
    }
    function mmExportForm(formId, task) {
        var f = document.getElementById(formId);
        f.querySelector('[name=task]').value = task;
        f.submit();
    }
    function mmCheckAll(toggle, formId, name) {
        var f = document.getElementById(formId);
        var cbs = f.querySelectorAll('input[name="' + name + '"]');
        for (var i = 0; i < cbs.length; i++) cbs[i].checked = toggle.checked;
    }
    function mmCheckAll2(toggle, formId, name, btnId) {
        var f = document.getElementById(formId);
        var cbs = f.querySelectorAll('input[name="' + name + '"]');
        for (var i = 0; i < cbs.length; i++) cbs[i].checked = toggle.checked;
        document.getElementById(btnId).disabled = !toggle.checked;
    }
    function mmToggleBtn(formId, name, btnId) {
        var f = document.getElementById(formId);
        var checked = f.querySelectorAll('input[name="' + name + '"]:checked');
        document.getElementById(btnId).disabled = checked.length === 0;
    }
    function mmMenuFilter(params) { mmApplyFilter('menus', params); }
    function mmArticleFilter(params) { mmApplyFilter('articles', params); }
    function mmExportCatForm() { document.getElementById('categoryForm').submit(); }
    function mmExportArticleForm() { document.getElementById('articleForm').submit(); }
    function mmUserFilter(params) { mmApplyFilter('users', params); }
    </script>

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
