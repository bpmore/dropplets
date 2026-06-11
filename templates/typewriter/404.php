<?php
use function Dropplets\e;
require __DIR__ . '/header.php';
?>
<div class="center-page"><p class="display">404</p><p>That page could not be found.</p>
<p><a href="<?= e($router->generate('home')) ?>">Go home</a></p></div>
<?php require __DIR__ . '/footer.php'; ?>
