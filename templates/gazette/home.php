<?php
use function Dropplets\e;
use function Dropplets\dpl_post_url;
use function Dropplets\dpl_excerpt;
require __DIR__ . '/header.php';
$dateFormat = i18n('dateformat', false);
$lead = $allPosts[0] ?? null;
$rest = array_slice($allPosts, 1);
?>
<?php if ($lead === null): ?>
    <p class="empty-state">Nothing here yet.</p>
<?php else: ?>
    <article class="lead-story">
        <p class="entry-meta"><?= e(date($dateFormat, (int) $lead['date'])) ?> &mdash; <?= e($lead['author']) ?></p>
        <h2 class="lead-title"><a href="<?= e(dpl_post_url($router, $lead)) ?>"><?= e($lead['title']) ?></a></h2>
        <?php if (!empty($lead['password'])): ?>
            <p class="entry-excerpt">&#128274; This dispatch is password-protected.</p>
        <?php elseif (($x = dpl_excerpt($lead, 320)) !== ''): ?>
            <p class="entry-excerpt lead-excerpt"><?= e($x) ?></p>
        <?php endif; ?>
    </article>
    <?php if (!empty($rest)): ?>
        <div class="column-rule"></div>
        <div class="story-columns">
            <?php foreach ($rest as $p): ?>
                <article class="entry">
                    <p class="entry-meta"><?= e(date($dateFormat, (int) $p['date'])) ?></p>
                    <h3 class="entry-title"><a href="<?= e(dpl_post_url($router, $p)) ?>"><?= e($p['title']) ?></a></h3>
                    <?php if (!empty($p['password'])): ?>
                        <p class="entry-excerpt">&#128274; Password-protected.</p>
                    <?php elseif (($x = dpl_excerpt($p, 150)) !== ''): ?>
                        <p class="entry-excerpt"><?= e($x) ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php if ($numPages > 1): ?>
    <nav aria-label="Pages"><ul class="pagination">
        <?php if ($page > 1): ?><li><a href="<?= e($page === 2 ? $router->generate('home') : $router->generate('posts', ['page' => $page - 1])) ?>" rel="prev">&larr; Later editions</a></li><?php endif; ?>
        <li class="page-state">Page <?= (int) $page ?> of <?= (int) $numPages ?></li>
        <?php if ($page < $numPages): ?><li><a href="<?= e($router->generate('posts', ['page' => $page + 1])) ?>" rel="next">Earlier editions &rarr;</a></li><?php endif; ?>
    </ul></nav>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
