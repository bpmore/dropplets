<?php
use function Dropplets\e;
use function Dropplets\dpl_post_url;
require __DIR__ . '/header.php';
$dateFormat = i18n('dateformat', false);
?>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">Nothing pinned up yet.</p>
<?php else: ?>
    <div class="post-grid">
        <?php foreach ($allPosts as $p): ?>
            <article class="polaroid">
                <?php if (!empty($p['password'])): ?>
                    <div class="photo photo-lock" aria-hidden="true">&#128274;</div>
                <?php elseif ($p['imageUrl'] !== ''): ?>
                    <img class="photo" src="<?= e($p['imageUrl']) ?>" alt="" loading="lazy">
                <?php elseif ($siteConfig['OGImage'] !== ''): ?>
                    <img class="photo" src="<?= e($siteConfig['OGImage']) ?>" alt="" loading="lazy">
                <?php else: ?>
                    <div class="photo photo-blank" aria-hidden="true">&#9998;</div>
                <?php endif; ?>
                <div class="caption">
                    <h2 class="card-title"><a href="<?= e(dpl_post_url($router, $p)) ?>"><?= e($p['title']) ?></a></h2>
                    <p class="card-meta"><?= e(date($dateFormat, (int) $p['date'])) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if ($numPages > 1): ?>
    <nav aria-label="Pages"><ul class="pagination">
        <?php if ($page > 1): ?><li><a href="<?= e($page === 2 ? $router->generate('home') : $router->generate('posts', ['page' => $page - 1])) ?>" rel="prev">&larr;</a></li><?php endif; ?>
        <li><span class="current"><?= (int) $page ?> / <?= (int) $numPages ?></span></li>
        <?php if ($page < $numPages): ?><li><a href="<?= e($router->generate('posts', ['page' => $page + 1])) ?>" rel="next">&rarr;</a></li><?php endif; ?>
    </ul></nav>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
