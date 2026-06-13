<?php
use function Fieldnote\e;
use function Fieldnote\fn_post_url;
use function Fieldnote\fn_pagination;
require __DIR__ . '/header.php';
Fieldnote\fn_search_status($searchQuery ?? null, count($allPosts ?? []));
$dateFormat = i18n('dateformat', false);
?>
<h1 class="sr-only"><?= e($siteName) ?></h1>
<?php if (empty($allPosts)): ?>
    <p class="empty-state">Nothing here yet.</p>
<?php else: ?>
    <ul class="zen-list">
    <?php foreach ($allPosts as $p): ?>
        <li>
            <a href="<?= e(fn_post_url($router, $p)) ?>"><?= e($p['title']) ?><?= !empty($p['password']) ? ' &#128274;' : '' ?></a>
            <time><?= e(date($dateFormat, (int) $p['date'])) ?></time>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php fn_pagination($router, $page, $numPages); ?>
<?php require __DIR__ . '/footer.php'; ?>
