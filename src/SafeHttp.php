<?php

namespace Fieldnote;

/**
 * SSRF-safe outbound HTTP, shared by remote image fetching and ActivityPub
 * (actor fetches and inbox deliveries go to arbitrary user-influenced
 * hosts — textbook SSRF surface). The recipe: resolve the host, reject
 * private/loopback/reserved ranges, pin curl to the exact IP that passed
 * validation (defeats DNS rebinding), never follow redirects.
 */
final class SafeHttp
{
    /**
     * Validate the URL (http/https, host resolves only to public addresses)
     * and return [host, port, ip] so callers can pin curl to the address
     * that passed validation.
     *
     * @return array{0:string,1:int,2:string}|null
     */
    public static function resolveTarget(string $url): ?array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = $parts['host'];
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : array_merge(gethostbynamel($host) ?: [], self::resolveAaaa($host));

        if ($ips === []) {
            return null;
        }

        // Test-only escape hatch: the smoke test federates with itself over
        // loopback. Never set in production.
        $allowPrivate = (bool) getenv('FN_AP_ALLOW_PRIVATE');

        foreach ($ips as $ip) {
            if (
                !$allowPrivate
                && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ) {
                return null; // any private/reserved address fails the whole URL
            }
        }
        return [$host, $port, $ips[0]];
    }

    /**
     * Pinned GET with size cap. Returns [status, body] or null when the
     * target failed validation or the transfer failed outright.
     *
     * @param string[] $headers
     * @return array{0:int,1:string}|null
     */
    public static function get(string $url, array $headers = [], int $timeout = 8, int $maxBytes = 1048576): ?array
    {
        $pinned = self::resolveTarget($url);
        if ($pinned === null) {
            return null;
        }
        [$host, $port, $ip] = $pinned;

        $ch = curl_init($url);
        curl_setopt_array($ch, self::baseOptions($host, $port, $ip, $timeout, $maxBytes) + [
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $body === false ? null : [$status, (string) $body];
    }

    /**
     * Pinned POST. Returns the HTTP status, 0 on transport failure or a
     * rejected target.
     *
     * @param string[] $headers
     */
    public static function post(string $url, string $body, array $headers = [], int $timeout = 8): int
    {
        $pinned = self::resolveTarget($url);
        if ($pinned === null) {
            return 0;
        }
        [$host, $port, $ip] = $pinned;

        $ch = curl_init($url);
        curl_setopt_array($ch, self::baseOptions($host, $port, $ip, $timeout, 262144) + [
            CURLOPT_POST       => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $status;
    }

    /** @return array<int,mixed> */
    private static function baseOptions(string $host, int $port, string $ip, int $timeout, int $maxBytes): array
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false, // no redirects: stops redirect-to-internal SSRF
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_RESOLVE        => ["{$host}:{$port}:{$ip}"],
            CURLOPT_MAXFILESIZE    => $maxBytes,
            CURLOPT_NOPROGRESS     => false,
            CURLOPT_PROGRESSFUNCTION => static fn ($c, $dlTotal, $dlNow) => ($dlNow > $maxBytes) ? 1 : 0,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Fieldnote/3.1 (+https://github.com/bpmore/fieldnote)',
        ];
    }

    /** @return string[] */
    private static function resolveAaaa(string $host): array
    {
        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        return array_column($records, 'ipv6');
    }
}
