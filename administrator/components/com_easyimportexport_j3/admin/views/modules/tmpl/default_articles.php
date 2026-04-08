<?php
defined('_JEXEC') or die;

$categories        = $this->categories;
$articles          = $this->articles;
$categoryList      = $this->categoryList;
$totalCats         = count($categories);
$totalArticles     = count($articles);
$publishedArticles = 0;
$featuredArticles  = 0;

foreach ($articles as $a) {
    if ((int) $a->state === 1) {
        $publishedArticles++;
    }
    if ((int) $a->featured === 1) {
        $featuredArticles++;
    }
}
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo $totalCats; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_TOTAL_CATEGORIES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $totalArticles; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_TOTAL_ARTICLES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $publishedArticles; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $featuredArticles; ?></div><div class="stat-label"><?php echo JText::_('COM_EASYIMPORTEXPORT_FEATURED'); ?></div></div>
</div>

<h4><i class="icon-folder"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_CATEGORIES'); ?></h4>

<div class="mm-toolbar">
    <button type="button" class="btn btn-success" onclick="mmExportCatForm()" id="btnExportCats" disabled>
        <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>
    <a class="btn" href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=articleexport.exportAll&what=categories&' . $token . '=1'); ?>">
        <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_CATEGORIES'); ?>
    </a>
</div>

<form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=articleexport.exportCategories'); ?>" method="post" id="categoryForm">
    <table class="table table-striped mm-table">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll2(this,'categoryForm','cid_categories[]','btnExportCats')" /></th>
                <th><?php echo JText::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:7%"><?php echo JText::_('JSTATUS'); ?></th>
                <th style="width:7%"><?php echo JText::_('JGRID_HEADING_ACCESS'); ?></th>
                <th style="width:7%"><?php echo JText::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo JText::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="6" class="center"><div class="alert"><?php echo JText::_('COM_EASYIMPORTEXPORT_NO_CATEGORIES'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($categories as $i => $cat):
                    $si     = isset($stateLabels[(int) $cat->published]) ? $stateLabels[(int) $cat->published] : array('Unknown', 'default');
                    $indent = str_repeat('<span class="mm-level-indent">—</span>', max(0, (int) $cat->level - 1));
                ?>
                    <tr>
                        <td><input type="checkbox" name="cid_categories[]" value="<?php echo (int) $cat->id; ?>" onclick="mmToggleBtn('categoryForm','cid_categories[]','btnExportCats')" /></td>
                        <td><?php echo $indent; ?> <strong><?php echo htmlspecialchars($cat->title); ?></strong> <small class="muted">(<?php echo htmlspecialchars($cat->alias); ?>)</small></td>
                        <td><span class="label label-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><?php echo htmlspecialchars(isset($cat->access_level) ? $cat->access_level : ''); ?></td>
                        <td><?php echo htmlspecialchars($cat->language); ?></td>
                        <td><?php echo (int) $cat->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="articleexport.exportCategories" />
    <?php echo JHtml::_('form.token'); ?>
</form>

<h4 style="margin-top:20px"><i class="icon-file-2"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_ARTICLES'); ?></h4>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchArticle" class="input-medium" placeholder="<?php echo JText::_('COM_EASYIMPORTEXPORT_ARTICLE_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['article_search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmArticleFilter({article_search:document.getElementById('mmSearchArticle').value})">
            <i class="icon-search"></i>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="mmExportArticleForm()" id="btnExportArticles" disabled>
        <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="icon-download"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?> <span class="caret"></span>
        </a>
        <ul class="dropdown-menu">
            <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=articleexport.exportAll&what=articles&' . $token . '=1'); ?>"><?php echo JText::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_ARTICLES'); ?></a></li>
            <?php foreach ($categoryList as $cl): ?>
                <li><a href="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=articleexport.exportAll&what=articles&filter_catid=' . (int) $cl->id . '&' . $token . '=1'); ?>">
                    <?php echo str_repeat('— ', max(0, (int) $cl->level - 1)) . htmlspecialchars($cl->title); ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <button type="button" class="btn btn-info" onclick="mmOpenModal('importArticlesModal')">
        <i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="input-medium" onchange="mmArticleFilter({filter_catid:this.value})">
        <option value="0"><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_CATEGORIES'); ?></option>
        <?php foreach ($categoryList as $cl): ?>
            <option value="<?php echo (int) $cl->id; ?>" <?php echo $filters['filter_catid'] === (int) $cl->id ? 'selected' : ''; ?>>
                <?php echo str_repeat('— ', max(0, (int) $cl->level - 1)) . htmlspecialchars($cl->title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select class="input-medium" onchange="mmArticleFilter({article_state:this.value})">
        <option value="-3" <?php echo $filters['article_state'] == -3 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="1" <?php echo $filters['article_state'] === 1 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></option>
        <option value="0" <?php echo $filters['article_state'] === 0 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_UNPUBLISHED'); ?></option>
        <option value="-2" <?php echo $filters['article_state'] === -2 ? 'selected' : ''; ?>><?php echo JText::_('COM_EASYIMPORTEXPORT_TRASHED'); ?></option>
    </select>
</div>

<form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=articleexport.exportArticles'); ?>" method="post" id="articleForm">
    <table class="table table-striped mm-table">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll2(this,'articleForm','cid_articles[]','btnExportArticles')" /></th>
                <th><?php echo JText::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:12%"><?php echo JText::_('COM_EASYIMPORTEXPORT_CATEGORY'); ?></th>
                <th style="width:7%"><?php echo JText::_('JSTATUS'); ?></th>
                <th style="width:5%"><?php echo JText::_('COM_EASYIMPORTEXPORT_FEATURED'); ?></th>
                <th style="width:10%"><?php echo JText::_('COM_EASYIMPORTEXPORT_CREATED'); ?></th>
                <th style="width:7%"><?php echo JText::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo JText::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articles)): ?>
                <tr><td colspan="8" class="center"><div class="alert"><?php echo JText::_('COM_EASYIMPORTEXPORT_NO_ARTICLES'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($articles as $i => $a):
                    $si = isset($stateLabels[(int) $a->state]) ? $stateLabels[(int) $a->state] : array('Unknown', 'default');
                ?>
                    <tr>
                        <td><input type="checkbox" name="cid_articles[]" value="<?php echo (int) $a->id; ?>" onclick="mmToggleBtn('articleForm','cid_articles[]','btnExportArticles')" /></td>
                        <td><strong><?php echo htmlspecialchars($a->title); ?></strong> <small class="muted">(<?php echo htmlspecialchars($a->alias); ?>)</small></td>
                        <td><span class="label"><?php echo htmlspecialchars(isset($a->category_title) ? $a->category_title : 'Uncategorised'); ?></span></td>
                        <td><span class="label label-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><?php echo (int) $a->featured ? '<i class="icon-star" style="color:#f0ad4e"></i>' : ''; ?></td>
                        <td><small><?php echo htmlspecialchars(isset($a->created) ? $a->created : ''); ?></small></td>
                        <td><?php echo htmlspecialchars($a->language); ?></td>
                        <td><?php echo (int) $a->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="articleexport.exportArticles" />
    <?php echo JHtml::_('form.token'); ?>
</form>

<!-- Import Articles Modal -->
<div class="modal hide fade" id="importArticlesModal" tabindex="-1" role="dialog">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h3><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_ARTICLES'); ?></h3>
    </div>
    <form action="<?php echo JRoute::_('index.php?option=com_easyimportexport&task=articleimport.import'); ?>" method="post" enctype="multipart/form-data">
        <div class="modal-body">
            <div class="alert"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_ARTICLES_NOTE'); ?></div>
            <div class="mm-import-zone" id="dzArticles">
                <div class="upload-icon"><i class="icon-upload"></i></div>
                <p><strong><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                <p class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                <input type="file" name="import_file_articles" id="fiArticles" accept=".json,.zip" style="display:none" />
                <button type="button" class="btn" onclick="document.getElementById('fiArticles').click()">
                    <i class="icon-folder-open"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                </button>
                <div id="sfArticles" class="mm-selected-file"><span class="label label-info" id="snArticles"></span></div>
            </div>
            <div style="margin-top:15px">
                <label class="checkbox">
                    <input type="checkbox" name="import_overwrite_articles" value="1" />
                    <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?>
                </label>
                <small class="muted"><?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC_ARTICLES'); ?></small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-dismiss="modal"><?php echo JText::_('JCANCEL'); ?></button>
            <button type="submit" class="btn btn-primary" id="biArticles" disabled><i class="icon-upload"></i> <?php echo JText::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
        </div>
        <?php echo JHtml::_('form.token'); ?>
    </form>
</div>

<script>
(function() {
    mmSetupFileInput('fiArticles','sfArticles','snArticles','biArticles');
    mmSetupDropZone('dzArticles','fiArticles');
    var s = document.getElementById('mmSearchArticle');
    if (s) s.addEventListener('keypress', function(e) { if (e.keyCode===13||e.key==='Enter'){e.preventDefault();mmArticleFilter({article_search:this.value});} });
})();
</script>
