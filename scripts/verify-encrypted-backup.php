<?php

$backupRoot = getenv('PREDEPLOY_BACKUP_ROOT');
$passwordFile = getenv('BACKUP_ARCHIVE_PASSWORD_FILE');

if (! is_string($backupRoot) || $backupRoot === '' || ! is_string($passwordFile) || $passwordFile === '') {
    throw new RuntimeException('Backup verification paths are not configured.');
}

$password = file_get_contents($passwordFile);
if ($password === false || trim($password) === '') {
    throw new RuntimeException('Unable to read a non-empty backup archive password file.');
}
$password = trim($password);

$archives = glob(rtrim($backupRoot, '/').'/*/*.zip') ?: [];
$archives = array_values(array_filter(
    $archives,
    fn (string $archive): bool => is_file($archive) && filemtime($archive) >= time() - 1800,
));

if ($archives === []) {
    throw new RuntimeException('No recent persistent database backup archive was found.');
}

usort($archives, fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));
$archivePath = $archives[0];
$archiveSize = filesize($archivePath);

if ($archiveSize === false || $archiveSize === 0) {
    throw new RuntimeException('The database backup archive is empty.');
}

$zip = new ZipArchive;
if ($zip->open($archivePath) !== true) {
    throw new RuntimeException('The database backup archive cannot be opened.');
}

$entries = 0;
$dataBytes = 0;
$sqlDumpBytes = 0;
$hasSqlDump = false;

for ($index = 0; $index < $zip->numFiles; $index++) {
    $entryName = $zip->getNameIndex($index);
    if (! is_string($entryName) || $entryName === '') {
        throw new RuntimeException('The database backup archive contains an invalid entry.');
    }

    if (str_ends_with($entryName, '/')) {
        continue;
    }

    $isSqlDump = str_ends_with(strtolower($entryName), '.sql')
        || str_ends_with(strtolower($entryName), '.sql.gz');
    $hasSqlDump = $hasSqlDump || $isSqlDump;

    $probe = new ZipArchive;
    if ($probe->open($archivePath) !== true) {
        throw new RuntimeException('The database backup archive cannot be re-opened.');
    }

    $unprotectedStream = @$probe->getStream($entryName);
    if ($unprotectedStream !== false) {
        fclose($unprotectedStream);
        $probe->close();
        throw new RuntimeException('The database backup entry is not encrypted.');
    }
    $probe->close();

    if (! $zip->setPassword($password)) {
        throw new RuntimeException('The database backup password could not be applied.');
    }

    $stream = $zip->getStream($entryName);
    if ($stream === false) {
        throw new RuntimeException('The encrypted database backup entry cannot be opened.');
    }

    while (! feof($stream)) {
        $chunk = fread($stream, 1048576);
        if ($chunk === false) {
            throw new RuntimeException('The database backup entry failed its integrity check.');
        }
        $dataBytes += strlen($chunk);
        if ($isSqlDump) {
            $sqlDumpBytes += strlen($chunk);
        }
    }

    fclose($stream);
    $entries++;
}

if (! $zip->close()) {
    throw new RuntimeException('The database backup archive could not be closed cleanly.');
}

if ($entries === 0 || $dataBytes === 0 || ! $hasSqlDump || $sqlDumpBytes === 0) {
    throw new RuntimeException('The archive does not contain a verified database dump.');
}

printf(
    "backup_verified encrypted=yes entries=%d data_bytes=%d archive_bytes=%d\n",
    $entries,
    $dataBytes,
    $archiveSize,
);
