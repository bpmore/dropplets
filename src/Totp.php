<?php

namespace Dropplets;

/**
 * Minimal RFC 6238 TOTP / RFC 4226 HOTP implementation. Pure PHP, no
 * dependencies: HMAC-SHA1 over a 30-second time counter with dynamic
 * truncation. Validated against the RFC 6238 Appendix B test vectors.
 */
final class Totp
{
    private const B32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const PERIOD = 30;
    public const DIGITS = 6;

    /** New random secret, base32 (the format authenticator apps accept). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function base32Encode(string $raw): string
    {
        $out  = '';
        $bits = 0;
        $value = 0;
        foreach (str_split($raw) as $chr) {
            $value = ($value << 8) | ord($chr);
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $out .= self::B32_ALPHABET[($value >> $bits) & 31];
            }
        }
        if ($bits > 0) {
            $out .= self::B32_ALPHABET[($value << (5 - $bits)) & 31];
        }
        return $out;
    }

    public static function base32Decode(string $b32): ?string
    {
        $b32 = strtoupper(str_replace([' ', '='], '', $b32));
        if ($b32 === '') {
            return null;
        }
        $out  = '';
        $bits = 0;
        $value = 0;
        foreach (str_split($b32) as $chr) {
            $pos = strpos(self::B32_ALPHABET, $chr);
            if ($pos === false) {
                return null;
            }
            $value = ($value << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($value >> $bits) & 0xFF);
            }
        }
        return $out;
    }

    /** RFC 4226 HOTP code for one counter value. */
    public static function hotp(string $secretB32, int $counter, int $digits = self::DIGITS): ?string
    {
        $key = self::base32Decode($secretB32);
        if ($key === null) {
            return null;
        }
        $hash   = hash_hmac('sha1', pack('J', $counter), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $bin = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);
        return str_pad((string) ($bin % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit code within ±$window time steps (default one step of
     * clock skew each way). Returns the matched counter so the caller can
     * record it and refuse replays, or null when nothing matches.
     */
    public static function verify(string $secretB32, string $code, int $window = 1, ?int $now = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return null;
        }
        $counter = intdiv($now ?? time(), self::PERIOD);
        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = self::hotp($secretB32, $counter + $offset);
            if ($candidate !== null && hash_equals($candidate, $code)) {
                return $counter + $offset;
            }
        }
        return null;
    }

    /** otpauth:// enrollment URI (what the QR code encodes). */
    public static function otpauthUri(string $secretB32, string $label, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($label)
            . '?secret=' . $secretB32
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }
}
