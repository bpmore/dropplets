<?php
use function Dropplets\e;
use function Dropplets\csrf_field;
require __DIR__ . '/header.php';
?>
<h1 class="setupH1 setup text-center">Two-Factor Verification</h1>
<div class="row"><div class="col-md-4"></div><div class="col-md-4">
<?php if (!empty($loginError)): ?>
    <div class="alert alert-danger" role="alert"><?= e($loginError) ?></div>
<?php endif; ?>
<form method="post" action="<?= e($router->generate('loginVerify')) ?>">
    <?= csrf_field() ?>
    <fieldset>
        <legend>Enter the code from your authenticator app:</legend>
        <input class="form-control" type="text" name="code" inputmode="numeric"
               autocomplete="one-time-code" placeholder="123456" required autofocus />
        <small class="text-muted">Lost your phone? Enter one of your recovery codes instead.</small>
    </fieldset>
    <input class="btn btn-primary mt-3" type="submit" value="Verify" />
</form>
</div><div class="col-md-4"></div></div>
<?php require __DIR__ . '/footer.php'; ?>
