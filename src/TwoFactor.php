<?php

namespace Dropplets;

/**
 * Optional TOTP second factor for the admin login.
 *
 * State lives in data/totp.php, outside the web root, separate from the main
 * config: the lost-authenticator escape hatch for a self-hoster is simply
 * deleting that file over SSH, which reverts login to password-only.
 *
 * Replay protection: the time-step counter of every accepted code is
 * persisted, and a code is only accepted if its counter is strictly newer —
 * an intercepted code can never be used a second time.
 */
final class TwoFactor
{
    private string $file;

    public function __construct(string $dataDir)
    {
        // JSON, not PHP: this file is rewritten on every accepted code (the
        // replay counter), and a require()'d PHP file can be served stale by
        // OPcache for up to revalidate_freq seconds after a write — which
        // would reopen the replay window the counter exists to close.
        $this->file = rtrim($dataDir, '/') . '/totp.json';
    }

    public function enabled(): bool
    {
        return is_file($this->file);
    }

    /** @param string[] $recoveryHashes password_hash()es of normalized codes */
    public function enable(string $secretB32, array $recoveryHashes): bool
    {
        return $this->save([
            'secret'      => $secretB32,
            'lastCounter' => 0,
            'recovery'    => array_values($recoveryHashes),
        ]);
    }

    public function disable(): bool
    {
        return !$this->enabled() || unlink($this->file);
    }

    /** Replay-protected TOTP check; persists the consumed counter. */
    public function verifyTotp(string $code): bool
    {
        $state = $this->load();
        if ($state === null) {
            return false;
        }
        $counter = Totp::verify((string) $state['secret'], $code);
        if ($counter === null || $counter <= (int) $state['lastCounter']) {
            return false;
        }
        $state['lastCounter'] = $counter;
        return $this->save($state);
    }

    /** Check a one-time recovery code and consume it on success. */
    public function useRecoveryCode(string $code): bool
    {
        $state = $this->load();
        if ($state === null) {
            return false;
        }
        $code = self::normalizeRecoveryCode($code);
        if ($code === '') {
            return false;
        }
        foreach ($state['recovery'] as $i => $hash) {
            if (password_verify($code, (string) $hash)) {
                unset($state['recovery'][$i]);
                $state['recovery'] = array_values($state['recovery']);
                return $this->save($state);
            }
        }
        return false;
    }

    public function recoveryCodesLeft(): int
    {
        return count($this->load()['recovery'] ?? []);
    }

    /** @return string[] plain codes to show the admin exactly once */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(5)));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
        }
        return $codes;
    }

    public static function normalizeRecoveryCode(string $code): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $code) ?? '');
    }

    /** @return array{secret:string,lastCounter:int,recovery:string[]}|null */
    private function load(): ?array
    {
        if (!$this->enabled()) {
            return null;
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : null;
    }

    private function save(array $data): bool
    {
        // _comment is for the admin reading the file over SSH.
        $data = ['_comment' => 'Dropplets two-factor state. Delete this file to '
            . 'fall back to password-only login (lost-authenticator recovery).'] + $data;

        $tmp = $this->file . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) === false) {
            return false;
        }
        @chmod($tmp, 0640);
        return rename($tmp, $this->file);
    }
}
