<?php
use function Dropplets\e;
use function Dropplets\dpl_render_head;
$siteName = $siteConfig['name'] !== '' ? $siteConfig['name'] : 'Dropplets';
?>
<!doctype html>
<html lang="en">
<head>
<?php dpl_render_head($siteConfig, $router, $pageTitle ?? '', $post ?? null, $router->generate('themeAsset', ['theme' => 'gazette', 'file' => 'theme.css'])); ?>
</head>
<body>
<header class="masthead">
    <p class="dateline"><?= e(date(i18n('dateformat', false))) ?></p>
    <h1 class="site-title-wrap"><a class="site-title" href="<?= e($router->generate('home')) ?>"><?= e($siteName) ?></a></h1>
    <?php if ($siteConfig['info'] !== ''): ?><p class="site-info"><?= e($siteConfig['info']) ?></p><?php endif; ?>
</header>
<main class="wrap">
