<?php
use function Dropplets\e;
use function Dropplets\csrf_field;

$needsQr = ($setupSecret !== null); // footer loads the QR renderer only for setup
require __DIR__ . '/header.php';
?>
<h1 class="setupH1 setup text-center">Two-Factor Login</h1>
<div class="row"><div class="col-md-3"></div><div class="col-md-6">

<?php if ($twoFaError !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= e($twoFaError) ?></div>
<?php endif; ?>

<?php if ($justEnabledCodes !== null): ?>
    <div class="alert alert-success" role="alert">Two-factor login is now <strong>enabled</strong>.</div>
    <h2 class="fs-5">Your recovery codes</h2>
    <p>Each works once if you lose access to your authenticator app. Store them
       somewhere safe — <strong>they will not be shown again</strong>.</p>
    <ul class="recovery-codes list-unstyled">
        <?php foreach ($justEnabledCodes as $rc): ?>
            <li><code><?= e($rc) ?></code></li>
        <?php endforeach; ?>
    </ul>
    <p class="text-muted small">Last-resort recovery: deleting <code>data/totp.json</code> on
       the server disables two-factor and reverts to password-only login.</p>
    <a class="btn btn-primary" href="<?= e($router->generate('dashboard')) ?>">Return To Dashboard</a>

<?php elseif ($twoFactor->enabled()): ?>
    <p>Two-factor login is <strong>enabled</strong>.
       Recovery codes remaining: <strong><?= (int) $twoFactor->recoveryCodesLeft() ?></strong></p>
    <form method="post" action="<?= e($router->generate('twofactor')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="twofaAction" value="disable">
        <fieldset>
            <legend class="fs-6">Disable two-factor login</legend>
            <input class="form-control" type="text" name="code" inputmode="numeric"
                   autocomplete="one-time-code" placeholder="Current code or recovery code" required />
        </fieldset>
        <button type="submit" class="btn btn-outline-danger mt-2">Disable</button>
        <a class="btn btn-secondary mt-2" href="<?= e($router->generate('settings')) ?>">Back To Settings</a>
    </form>

<?php else: ?>
    <ol>
        <li>Scan this QR code with an authenticator app (1Password, Google
            Authenticator, Authy, Apple Passwords, …) — or type the key in manually.</li>
        <li>Enter the 6-digit code the app shows to confirm and switch it on.</li>
    </ol>
    <div id="totpQr" data-otpauth="<?= e($otpauthUri) ?>" class="my-3"></div>
    <p class="text-center"><code class="totp-secret"><?= e(trim(chunk_split($setupSecret, 4, ' '))) ?></code></p>
    <form method="post" action="<?= e($router->generate('twofactor')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="twofaAction" value="enable">
        <input class="form-control" type="text" name="code" inputmode="numeric"
               autocomplete="one-time-code" placeholder="123456" required autofocus />
        <button type="submit" class="btn btn-primary mt-2">Confirm &amp; Enable</button>
        <a class="btn btn-secondary mt-2" href="<?= e($router->generate('settings')) ?>">Cancel</a>
    </form>
<?php endif; ?>

</div><div class="col-md-3"></div></div>
<?php require __DIR__ . '/footer.php'; ?>
