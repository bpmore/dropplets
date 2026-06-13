<?php

use function Fieldnote\e;
use function Fieldnote\fn_render_head;
use function Fieldnote\fn_skip_link;

$siteName = $siteConfig['name'] !== '' ? $siteConfig['name'] : 'Fieldnote';
?>
<!doctype html>
<html lang="en">

<head>
    <?php fn_render_head(
        $siteConfig,
        $router,
        $pageTitle ?? '',
        $post ?? null,
        $router->generate('themeAsset', ['theme' => 'neon', 'file' => 'theme.css'])
    ); ?>
</head>

<body>
    <?php fn_skip_link(); ?>
<?php Fieldnote\fn_utility_bar($router, $siteConfig); ?>
    <header class="hud-bar">
        <a class="site-title" href="<?= e($router->generate('home')) ?>"><?= e($siteName) ?></a>
        <?php if ($siteConfig['info'] !== ''): ?>
            <span class="site-info"><?= e($siteConfig['info']) ?></span>
        <?php endif; ?>
    </header>
    <main id="main">
