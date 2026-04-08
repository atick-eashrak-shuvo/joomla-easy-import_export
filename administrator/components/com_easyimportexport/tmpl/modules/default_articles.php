<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$categories = $this->categories;
$articles = $this->articles;
$categoryList = $this->categoryList;
$totalCats = count($categories);
$totalArticles = count($articles);
$publishedArticles = 0;
$featuredArticles = 0;
foreach ($articles as $a) {
    if ((int)$a->state === 1) $publishedArticles++;
    if ((int)$a->featured === 1) $featuredArticles++;
}
?>

<div class="mm-stats">
    <div class="stat-card"><div class="stat-value"><?php echo $totalCats; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_TOTAL_CATEGORIES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $totalArticles; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_TOTAL_ARTICLES'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $publishedArticles; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></div></div>
    <div class="stat-card"><div class="stat-value"><?php echo $featuredArticles; ?></div><div class="stat-label"><?php echo Text::_('COM_EASYIMPORTEXPORT_FEATURED'); ?></div></div>
</div>

<!-- ═══════ CATEGORIES SECTION ═══════ -->
<h4 class="mb-3 mt-2"><span class="icon-folder"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_CATEGORIES'); ?></h4>

<div class="mm-toolbar">
    <button type="button" class="btn btn-success" onclick="mmExportCatForm()" id="btnExportCats" disabled>
        <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>
    <a class="btn btn-outline-success" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=articleexport.exportAll&what=categories&' . $token . '=1'); ?>">
        <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_CATEGORIES'); ?>
    </a>
</div>

<form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=articleexport.exportCategories'); ?>" method="post" id="categoryForm">
    <table class="table mm-table table-striped">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll2(this,'categoryForm','cid_categories[]','btnExportCats')" /></th>
                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:7%"><?php echo Text::_('JSTATUS'); ?></th>
                <th style="width:7%"><?php echo Text::_('JGRID_HEADING_ACCESS'); ?></th>
                <th style="width:7%"><?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr><td colspan="6" class="text-center"><div class="alert alert-info mb-0"><?php echo Text::_('COM_EASYIMPORTEXPORT_NO_CATEGORIES'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($categories as $i => $cat):
                    $si = $stateLabels[(int)$cat->published] ?? ['Unknown','secondary'];
                    $indent = str_repeat('<span class="mm-level-indent">—</span>', max(0, (int)$cat->level - 1));
                ?>
                    <tr>
                        <td><input type="checkbox" name="cid_categories[]" value="<?php echo (int)$cat->id; ?>" onclick="mmToggleBtn('categoryForm','cid_categories[]','btnExportCats')" /></td>
                        <td><?php echo $indent; ?> <strong><?php echo htmlspecialchars($cat->title); ?></strong> <small class="text-muted">(<?php echo htmlspecialchars($cat->alias); ?>)</small></td>
                        <td><span class="badge bg-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><?php echo htmlspecialchars($cat->access_level ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cat->language); ?></td>
                        <td><?php echo (int)$cat->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="articleexport.exportCategories" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<!-- ═══════ ARTICLES SECTION ═══════ -->
<h4 class="mb-3 mt-4"><span class="icon-file-alt"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_ARTICLES'); ?></h4>

<div class="mm-toolbar">
    <div class="mm-search-group">
        <input type="text" id="mmSearchArticle" class="form-control" placeholder="<?php echo Text::_('COM_EASYIMPORTEXPORT_ARTICLE_SEARCH_PLACEHOLDER'); ?>"
               value="<?php echo htmlspecialchars($filters['article_search'], ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" class="btn btn-primary" onclick="mmArticleFilter({article_search:document.getElementById('mmSearchArticle').value})">
            <span class="icon-search"></span>
        </button>
    </div>

    <button type="button" class="btn btn-success" onclick="mmExportArticleForm()" id="btnExportArticles" disabled>
        <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_SELECTED'); ?>
    </button>

    <div class="btn-group">
        <button type="button" class="btn btn-outline-success dropdown-toggle" data-bs-toggle="dropdown">
            <span class="icon-download"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL'); ?>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=articleexport.exportAll&what=articles&' . $token . '=1'); ?>"><?php echo Text::_('COM_EASYIMPORTEXPORT_EXPORT_ALL_ARTICLES'); ?></a></li>
            <?php foreach ($categoryList as $cl): ?>
                <li><a class="dropdown-item" href="<?php echo Route::_('index.php?option=com_easyimportexport&task=articleexport.exportAll&what=articles&filter_catid=' . (int)$cl->id . '&' . $token . '=1'); ?>">
                    <?php echo str_repeat('— ', max(0,(int)$cl->level-1)) . htmlspecialchars($cl->title); ?>
                </a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <button type="button" class="btn btn-info text-white" onclick="mmOpenModal('importArticlesModal')">
        <span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT'); ?>
    </button>
</div>

<div class="mm-filter-bar">
    <select class="form-select" style="width:auto" onchange="mmArticleFilter({filter_catid:this.value})">
        <option value="0"><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_CATEGORIES'); ?></option>
        <?php foreach ($categoryList as $cl): ?>
            <option value="<?php echo (int)$cl->id; ?>" <?php echo $filters['filter_catid']===(int)$cl->id?'selected':''; ?>>
                <?php echo str_repeat('— ', max(0,(int)$cl->level-1)) . htmlspecialchars($cl->title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" style="width:auto" onchange="mmArticleFilter({article_state:this.value})">
        <option value="-3" <?php echo $filters['article_state']==-3?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_ALL_STATES'); ?></option>
        <option value="1" <?php echo $filters['article_state']===1?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_PUBLISHED'); ?></option>
        <option value="0" <?php echo $filters['article_state']===0?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_UNPUBLISHED'); ?></option>
        <option value="-2" <?php echo $filters['article_state']==-2?'selected':''; ?>><?php echo Text::_('COM_EASYIMPORTEXPORT_TRASHED'); ?></option>
    </select>
</div>

<form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=articleexport.exportArticles'); ?>" method="post" id="articleForm">
    <table class="table mm-table table-striped">
        <thead>
            <tr>
                <th style="width:1%"><input type="checkbox" onclick="mmCheckAll2(this,'articleForm','cid_articles[]','btnExportArticles')" /></th>
                <th><?php echo Text::_('JGLOBAL_TITLE'); ?></th>
                <th style="width:12%"><?php echo Text::_('COM_EASYIMPORTEXPORT_CATEGORY'); ?></th>
                <th style="width:7%"><?php echo Text::_('JSTATUS'); ?></th>
                <th style="width:5%"><?php echo Text::_('COM_EASYIMPORTEXPORT_FEATURED'); ?></th>
                <th style="width:10%"><?php echo Text::_('COM_EASYIMPORTEXPORT_CREATED'); ?></th>
                <th style="width:7%"><?php echo Text::_('JGRID_HEADING_LANGUAGE'); ?></th>
                <th style="width:4%"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articles)): ?>
                <tr><td colspan="8" class="text-center"><div class="alert alert-info mb-0"><?php echo Text::_('COM_EASYIMPORTEXPORT_NO_ARTICLES'); ?></div></td></tr>
            <?php else: ?>
                <?php foreach ($articles as $i => $a):
                    $si = $stateLabels[(int)$a->state] ?? ['Unknown','secondary'];
                ?>
                    <tr>
                        <td><input type="checkbox" name="cid_articles[]" value="<?php echo (int)$a->id; ?>" onclick="mmToggleBtn('articleForm','cid_articles[]','btnExportArticles')" /></td>
                        <td><strong><?php echo htmlspecialchars($a->title); ?></strong> <small class="text-muted">(<?php echo htmlspecialchars($a->alias); ?>)</small></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($a->category_title ?? 'Uncategorised'); ?></span></td>
                        <td><span class="badge bg-<?php echo $si[1]; ?>"><?php echo $si[0]; ?></span></td>
                        <td><?php echo (int)$a->featured ? '<span class="icon-star text-warning"></span>' : ''; ?></td>
                        <td><small><?php echo htmlspecialchars($a->created ?? ''); ?></small></td>
                        <td><?php echo htmlspecialchars($a->language); ?></td>
                        <td><?php echo (int)$a->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="task" value="articleexport.exportArticles" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<!-- Import Articles/Categories Modal -->
<div class="modal fade mm-modal" id="importArticlesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title"><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_ARTICLES'); ?></h5>
            <button type="button" class="btn-close" onclick="mmCloseModal('importArticlesModal')"></button>
        </div>
        <form action="<?php echo Route::_('index.php?option=com_easyimportexport&task=articleimport.import'); ?>" method="post" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="alert alert-secondary">
                    <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_ARTICLES_NOTE'); ?>
                </div>
                <div class="mm-import-zone" id="dzArticles">
                    <div class="upload-icon"><span class="icon-upload"></span></div>
                    <p class="mb-2"><strong><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_DROP_FILE'); ?></strong></p>
                    <p class="text-muted mb-3"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OR_BROWSE'); ?></p>
                    <input type="file" name="import_file_articles" id="fiArticles" accept=".json,.zip" class="d-none" />
                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fiArticles').click()">
                        <span class="icon-folder-open"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_BROWSE'); ?>
                    </button>
                    <div id="sfArticles" class="mm-selected-file mt-2"><span class="badge bg-info p-2" id="snArticles"></span></div>
                </div>
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="import_overwrite_articles" value="1" id="owArticles">
                        <label class="form-check-label" for="owArticles"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE'); ?></label>
                    </div>
                    <small class="text-muted"><?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_OVERWRITE_DESC_ARTICLES'); ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="mmCloseModal('importArticlesModal')"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="submit" class="btn btn-primary" id="biArticles" disabled><span class="icon-upload"></span> <?php echo Text::_('COM_EASYIMPORTEXPORT_IMPORT_START'); ?></button>
            </div>
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>
    </div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    mmSetupFileInput('fiArticles','sfArticles','snArticles','biArticles');
    mmSetupDropZone('dzArticles','fiArticles');
    document.getElementById('mmSearchArticle').addEventListener('keypress', function(e) {
        if (e.key==='Enter') { e.preventDefault(); mmArticleFilter({article_search:this.value}); }
    });
});
</script>
