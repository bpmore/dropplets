<?php

namespace Fieldnote;

/**
 * Passkey (WebAuthn) credential storage. See docs/passkeys-spec.md.
 *
 * Mirrors TwoFactor's shape and lifecycle: JSON in data/passkeys.json,
 * outside the web root, rewritten on every login (the sign-count is replay
 * protection), and deleting the file over SSH disables passkey login — the
 * lost-device escape hatch. Password (+ TOTP) login is always a fallback.
 */
final class Passkeys
{
    private string $file;

    public function __construct(string $dataDir)
    {
        $this->file = rtrim($dataDir, '/') . '/passkeys.json';
    }

    public function enabled(): bool
    {
        return $this->list() !== [];
    }

    /** @return list<array{id:string,publicKey:string,signCount:int,label:string,createdAt:int}> */
    public function list(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return array_values((array) ($data['credentials'] ?? []));
    }

    /** @return array{id:string,publicKey:string,signCount:int,label:string,createdAt:int}|null */
    public function find(string $id): ?array
    {
        foreach ($this->list() as $credential) {
            if (hash_equals((string) $credential['id'], $id)) {
                return $credential;
            }
        }
        return null;
    }

    public function add(string $id, string $publicKeyPem, int $signCount, string $label): bool
    {
        $credentials   = $this->list();
        $credentials[] = [
            'id'        => $id,
            'publicKey' => $publicKeyPem,
            'signCount' => $signCount,
            'label'     => $label !== '' ? $label : 'Passkey',
            'createdAt' => time(),
        ];
        return $this->save($credentials);
    }

    public function remove(string $id): bool
    {
        $kept = array_values(array_filter(
            $this->list(),
            static fn (array $c): bool => !hash_equals((string) $c['id'], $id)
        ));
        if ($kept === []) {
            return !is_file($this->file) || unlink($this->file);
        }
        return $this->save($kept);
    }

    public function updateSignCount(string $id, int $count): bool
    {
        $credentials = $this->list();
        foreach ($credentials as $i => $credential) {
            if (hash_equals((string) $credential['id'], $id)) {
                $credentials[$i]['signCount'] = $count;
                return $this->save($credentials);
            }
        }
        return false;
    }

    public static function b64uEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    public static function b64uDecode(string $encoded): string
    {
        return (string) base64_decode(strtr($encoded, '-_', '+/'), false);
    }

    /** @param list<array<string,mixed>> $credentials */
    private function save(array $credentials): bool
    {
        $data = [
            '_comment' => 'Fieldnote passkeys. Delete this file to disable '
                . 'passkey login (lost-device recovery); password login is unaffected.',
            'credentials' => $credentials,
        ];
        $tmp = $this->file . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX) === false) {
            return false;
        }
        @chmod($tmp, 0640);
        return rename($tmp, $this->file);
    }
}
