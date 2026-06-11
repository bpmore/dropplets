<?php
use function Dropplets\e;
use function Dropplets\dpl_render_head;
$siteName = $siteConfig['name'] !== '' ? $siteConfig['name'] : 'Dropplets';
?>
<!doctype html>
<html lang="en">
<head>
<?php dpl_render_head($siteConfig, $router, $pageTitle ?? '', $post ?? null, $router->generate('themeAsset', ['theme' => 'bink', 'file' => 'theme.css'])); ?>
</head>
<body>
<header class="masthead">
    <div class="masthead-inner">
        <h1 class="site-title"><a href="<?= e($router->generate('home')) ?>"><?= e($siteName) ?></a></h1>
        <?php if ($siteConfig['info'] !== ''): ?><p class="site-info"><?= e($siteConfig['info']) ?></p><?php endif; ?>
        <ul class="masthead-links">
            <li><a href="<?= e($router->generate('feed')) ?>">RSS feed</a></li>
        </ul>
    </div>
</header>
<main class="wrap">
