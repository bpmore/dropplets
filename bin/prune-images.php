<?php

/**
 * Orphaned-image sweeper. CLI only.
 *
 *   php bin/prune-images.php            report orphans (dry run)
 *   php bin/prune-images.php --delete   remove them
 *
 * Finds two kinds of garbage left behind before image replacement learned
 * to clean up after itself:
 *   - image records no post references
 *   - files under public/uploads/ no image record references
 *
 * Records and files referenced by ANY post (draft or published) are never
 * touched.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

// SleekDB triggers implicit-nullable deprecations on PHP 8.4 (same
// suppression bootstrap.php applies).
error_reporting(E_ALL & ~E_DEPRECATED);

require dirname(__DIR__) . '/vendor/autoload.php';

use SleekDB\Store;

$root      = dirname(__DIR__);
$dbDir     = $root . '/data/siteDatabase';
$uploadDir = $root . '/public/uploads';
$delete    = in_array('--delete', $argv, true);

if (!is_dir($dbDir . '/images')) {
    echo "No image store found — nothing to prune.\n";
    exit(0);
}

$dbOptions  = ['timeout' => false];
$blogStore  = new Store('blog', $dbDir, $dbOptions);
$imageStore = new Store('images', $dbDir, $dbOptions);

// Resolve a stored path (relative to uploads/, or absolute pre-migration).
$resolve = static function (string $path) use ($uploadDir): string {
    if ($path === '') {
        return '';
    }
    return str_starts_with($path, '/') ? $path : $uploadDir . '/' . $path;
};

$referencedIds = [];
foreach ($blogStore->findAll() as $post) {
    if (isset($post['image']) && is_numeric($post['image'])) {
        $referencedIds[(int) $post['image']] = true;
    }
}

$orphanRecords = []; // [id, resolvedPath]
$keptFiles     = []; // realpath => true, files owned by referenced records
foreach ($imageStore->findAll() as $record) {
    $id   = (int) $record['_id'];
    $path = $resolve((string) ($record['path'] ?? ''));
    if (!isset($referencedIds[$id])) {
        $orphanRecords[] = [$id, $path];
    } elseif ($path !== '' && ($real = realpath($path)) !== false) {
        $keptFiles[$real] = true;
    }
}

// Files owned by orphan records get removed with their record; exclude them
// from the recordless-file list so nothing is reported (or deleted) twice.
$orphanRecordFiles = [];
foreach ($orphanRecords as [, $path]) {
    if ($path !== '' && ($real = realpath($path)) !== false) {
        $orphanRecordFiles[$real] = true;
    }
}

$orphanFiles = [];
if (is_dir($uploadDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $file) {
        // Only file types ImageHandler writes; leaves .htaccess/.gitkeep
        // and anything an admin placed there by hand alone.
        if (!$file->isFile() || !in_array(strtolower($file->getExtension()), ['jpg', 'png', 'gif'], true)) {
            continue;
        }
        $real = $file->getRealPath();
        if (!isset($keptFiles[$real]) && !isset($orphanRecordFiles[$real])) {
            $orphanFiles[] = $real;
        }
    }
}

if ($orphanRecords === [] && $orphanFiles === []) {
    echo "Clean: every image record is referenced by a post and every upload has a record.\n";
    exit(0);
}

foreach ($orphanRecords as [$id, $path]) {
    printf("record #%d (no post references it)%s\n", $id, $path !== '' ? ' + ' . $path : '');
    if ($delete) {
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
        $imageStore->deleteById($id);
    }
}
foreach ($orphanFiles as $path) {
    printf("file %s (no image record references it)\n", $path);
    if ($delete) {
        @unlink($path);
    }
}

printf(
    "\n%d orphan record(s), %d orphan file(s) %s.\n",
    count($orphanRecords),
    count($orphanFiles),
    $delete ? 'deleted' : 'found — re-run with --delete to remove'
);
exit(0);
