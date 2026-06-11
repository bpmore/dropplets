<?php
use function Dropplets\e;
use function Dropplets\dpl_post_url;
use function Dropplets\dpl_excerpt;
require __DIR__ . '/header.php';
$dateFormat = i18n('dateformat', false);
?>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">Nothing here yet.</p>
<?php else: ?>
    <?php foreach ($allPosts as $p): ?>
        <article class="row-entry">
            <div class="row-meta">
                <time><?= e(date($dateFormat, (int) $p['date'])) ?></time>
                <span class="row-author"><?= e($p['author']) ?></span>
            </div>
            <div class="row-body">
                <h2 class="entry-title"><a href="<?= e(dpl_post_url($router, $p)) ?>"><?= e($p['title']) ?></a></h2>
                <?php if (!empty($p['password'])): ?>
                    <p class="entry-excerpt">&#128274; This post is password-protected.</p>
                <?php elseif (($x = dpl_excerpt($p, 200)) !== ''): ?>
                    <p class="entry-excerpt"><?= e($x) ?></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
<?php if ($numPages > 1): ?>
    <nav aria-label="Pages"><ul class="pagination">
        <?php if ($page > 1): ?><li><a href="<?= e($page === 2 ? $router->generate('home') : $router->generate('posts', ['page' => $page - 1])) ?>" rel="prev">&larr; Newer</a></li><?php endif; ?>
        <li class="page-state"><?= (int) $page ?> / <?= (int) $numPages ?></li>
        <?php if ($page < $numPages): ?><li><a href="<?= e($router->generate('posts', ['page' => $page + 1])) ?>" rel="next">Older &rarr;</a></li><?php endif; ?>
    </ul></nav>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
