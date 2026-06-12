<?php
use function Fieldnote\e;
use function Fieldnote\csrf_field;
require __DIR__ . '/header.php';
?>
<h1 class="setupH1 setup text-center"><?php i18n("login_title"); ?></h1>
<div class="row"><div class="col-md-4"></div><div class="col-md-4">
<?php if (!empty($loginError)): ?>
    <div class="alert alert-danger" role="alert"><?= e($loginError) ?></div>
<?php endif; ?>
<?php if (!empty($passkeysEnabled)): ?>
    <div class="text-center mb-3">
        <button type="button" id="passkeyLogin" class="btn btn-success w-100"
                data-options-url="<?= e($router->generate('passkeyLoginOptions')) ?>"
                data-verify-url="<?= e($router->generate('passkeyLoginVerify')) ?>"
                data-csrf="<?= e(Fieldnote\Security::csrfToken()) ?>">
            Sign in with a passkey
        </button>
        <p id="passkeyLoginMsg" class="small mb-0" role="status"></p>
        <p class="text-muted small mt-2 mb-0">&mdash; or use your password &mdash;</p>
    </div>
<?php endif; ?>
<form method="post" action="<?= e($router->generate('login')) ?>">
    <?= csrf_field() ?>
    <fieldset>
        <legend><?php i18n("login_password_legend"); ?></legend>
        <input class="form-control" type="password" name="blogPassword" autocomplete="current-password" placeholder="<?php i18n("login_password_placeholder"); ?>" required autofocus />
    </fieldset>
    <input class="btn btn-primary mt-3" type="submit" value="<?php i18n("login_submit"); ?>" />
</form>
</div><div class="col-md-4"></div></div>
<?php require __DIR__ . '/footer.php'; ?>
