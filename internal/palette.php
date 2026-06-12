<?php
use function Fieldnote\e;
use function Fieldnote\csrf_field;
use Fieldnote\Wcag;
require __DIR__ . '/header.php';

$fieldId = static fn (string $scheme, string $tok): string => 'tok-' . $scheme . '-' . ltrim($tok, '-');
?>
<h1 class="setupH1 setup text-center">Palette</h1>
<p class="text-center text-muted">
    Adjust <strong><?= e($siteConfig['template']) ?></strong>'s colors. Every change is checked against
    WCAG 2.2 AA before it can be saved — a palette that fails contrast is corrected, never published.
</p>
<div class="text-center mb-4">
    <a href="<?= e($router->generate('themes')) ?>" class="btn btn-secondary">Themes</a>
    <a href="<?= e($router->generate('dashboard')) ?>" class="btn btn-secondary"><?php i18n("settings_dashboard_return"); ?></a>
</div>

<?php if ($savedNotice !== ''): ?>
    <div class="alert alert-success text-center" role="status"><?= e($savedNotice) ?></div>
<?php endif; ?>

<?php if ($failures !== []): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Not saved — these pairs miss WCAG 2.2 AA:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($failures as $scheme => $list): foreach ($list as $f): ?>
                <li>
                    [<?= e($scheme) ?>] <code><?= e($f['fg']) ?></code> on <code><?= e($f['bg']) ?></code>
                    is <?= sprintf('%.2f', $f['ratio']) ?>:1, needs <?= sprintf('%.1f', $f['min']) ?>:1.
                    <?php if ($f['suggest'] !== null): ?>
                        Nearest passing shade:
                        <input type="color" value="<?= e($f['suggest']) ?>" disabled
                               aria-label="Suggested color <?= e($f['suggest']) ?>" class="palette-swatch">
                        <code><?= e($f['suggest']) ?></code>
                    <?php endif; ?>
                </li>
            <?php endforeach; endforeach; ?>
        </ul>
    </div>
    <?php if ($suggestedValues !== null): ?>
        <form method="post" action="<?= e($router->generate('palette')) ?>" class="text-center mb-4">
            <?= csrf_field() ?>
            <?php foreach ($suggestedValues as $scheme => $tokens): foreach ($tokens as $tok => $val): ?>
                <input type="hidden" name="tok[<?= e($scheme) ?>][<?= e($tok) ?>]" value="<?= e($val) ?>">
            <?php endforeach; endforeach; ?>
            <button type="submit" class="btn btn-success">Apply suggested fixes</button>
        </form>
    <?php endif; ?>
<?php endif; ?>

<form method="post" action="<?= e($router->generate('palette')) ?>">
    <?= csrf_field() ?>
    <div class="palette-grid">
        <?php foreach (['light' => 'Light scheme', 'dark' => 'Dark scheme'] as $scheme => $legend): ?>
            <fieldset class="palette-scheme">
                <legend><?= e($legend) ?></legend>
                <?php foreach (Wcag::REQUIRED_TOKENS as $tok): ?>
                    <div class="palette-row">
                        <label for="<?= e($fieldId($scheme, $tok)) ?>">
                            <code><?= e($tok) ?></code>
                            <span class="text-muted"><?= e(Wcag::TOKEN_ROLES[$tok]) ?></span>
                        </label>
                        <input type="color" id="<?= e($fieldId($scheme, $tok)) ?>"
                               name="tok[<?= e($scheme) ?>][<?= e($tok) ?>]"
                               value="<?= e($values[$scheme][$tok]) ?>">
                        <?php if ($values[$scheme][$tok] !== $themeDefaults[$scheme][$tok]): ?>
                            <small class="text-muted">theme default <?= e($themeDefaults[$scheme][$tok]) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-3">
        <button type="submit" class="btn btn-primary">Check &amp; save palette</button>
    </div>
</form>

<?php if ($saved !== []): ?>
    <form method="post" action="<?= e($router->generate('palette')) ?>" class="text-center mt-3">
        <?= csrf_field() ?>
        <input type="hidden" name="paletteAction" value="reset">
        <button type="submit" class="btn btn-sm btn-outline-danger">Reset to theme defaults</button>
    </form>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
