<?php
use function Dropplets\e;
use function Dropplets\dpl_post_url;
require __DIR__ . '/header.php';
$dateFormat = i18n('dateformat', false);
?>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">Nothing here yet.</p>
<?php else: ?>
    <ul class="zen-list">
    <?php foreach ($allPosts as $p): ?>
        <li>
            <a href="<?= e(dpl_post_url($router, $p)) ?>"><?= e($p['title']) ?><?= !empty($p['password']) ? ' &#128274;' : '' ?></a>
            <time><?= e(date($dateFormat, (int) $p['date'])) ?></time>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php if ($numPages > 1): ?>
    <nav aria-label="Pages"><ul class="pagination">
        <?php if ($page > 1): ?><li><a href="<?= e($page === 2 ? $router->generate('home') : $router->generate('posts', ['page' => $page - 1])) ?>" rel="prev">&larr;</a></li><?php endif; ?>
        <li class="page-state"><?= (int) $page ?> / <?= (int) $numPages ?></li>
        <?php if ($page < $numPages): ?><li><a href="<?= e($router->generate('posts', ['page' => $page + 1])) ?>" rel="next">&rarr;</a></li><?php endif; ?>
    </ul></nav>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
