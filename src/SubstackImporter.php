<?php

namespace Fieldnote;

/**
 * Substack export → normalized Porter entries. docs/importers-spec.md.
 *
 * A Substack export is a zip of `posts.csv` (one row per post: post_id, title,
 * subtitle, post_date, is_published, …) plus `posts/<post_id>.<slug>.html`
 * bodies. Rows are matched to their HTML file by post_id; the subtitle (the
 * deck) is preserved as an emphasized lead. Porter converts the HTML to
 * Markdown and localizes the inline images. No tags (Substack exports none).
 */
final class SubstackImporter
{
    /** Cheap structural check: a zip that contains posts.csv. */
    public static function looksLikeSubstack(string $zipPath): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }
        $found = $zip->locateName('posts.csv', \ZipArchive::FL_NOCASE) !== false;
        $zip->close();
        return $found;
    }

    /** @return list<array<string,mixed>> */
    public static function parse(string $zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [];
        }
        $csv = $zip->getFromName('posts.csv', 0, \ZipArchive::FL_NOCASE);
        if ($csv === false) {
            $zip->close();
            return [];
        }

        // Index the HTML bodies by post_id (the filename's leading segment).
        $htmlByPostId = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (preg_match('#(?:^|/)posts/([^/]+)\.html$#i', $name, $m)) {
                $base = $m[1];
                $pid  = str_contains($base, '.') ? substr($base, 0, strpos($base, '.')) : $base;
                $slug = str_contains($base, '.') ? substr($base, strpos($base, '.') + 1) : '';
                $htmlByPostId[$pid] = ['name' => $name, 'slug' => $slug];
            }
        }

        $entries = [];
        foreach (self::parseCsv($csv) as $row) {
            $pid = (string) ($row['post_id'] ?? '');
            if ($pid === '' || !isset($htmlByPostId[$pid])) {
                continue;
            }
            $html = (string) $zip->getFromName($htmlByPostId[$pid]['name']);
            $subtitle = trim((string) ($row['subtitle'] ?? ''));
            if ($subtitle !== '') {
                $html = '<p><em>' . htmlspecialchars($subtitle, ENT_QUOTES) . '</em></p>' . $html;
            }
            $title = trim((string) ($row['title'] ?? ''));
            $slug  = $htmlByPostId[$pid]['slug'];
            $date  = (string) ($row['post_date'] ?? $row['email_sent_at'] ?? '');
            $entries[] = [
                'title'  => $title !== '' ? $title : ($slug !== '' ? $slug : $pid),
                'slug'   => $slug !== '' ? $slug : $title,
                'date'   => ($date !== '' ? strtotime($date) : false) ?: time(),
                'tags'   => [],
                'author' => '',
                'html'   => $html,
                'source' => $title !== '' ? $title : $slug,
            ];
        }
        $zip->close();
        return $entries;
    }

    /**
     * Parse the CSV into header-keyed rows. Goes through a temp stream so
     * fgetcsv handles quoted fields with embedded commas and newlines.
     *
     * @return list<array<string,string>>
     */
    private static function parseCsv(string $csv): array
    {
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            return [];
        }
        fwrite($fh, $csv);
        rewind($fh);

        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);
            return [];
        }
        $header = array_map(static fn ($h): string => strtolower(trim((string) $h)), $header);
        $width  = count($header);

        $rows = [];
        while (($row = fgetcsv($fh)) !== false) {
            if ($row === [null]) {
                continue; // blank line
            }
            $row    = array_pad(array_slice($row, 0, $width), $width, '');
            $rows[] = array_combine($header, $row);
        }
        fclose($fh);
        return $rows;
    }
}
