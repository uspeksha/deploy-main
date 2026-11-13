<?php

use Spatie\Backup\Events\BackupZipWasCreated;
use Spatie\Backup\Listeners\EncryptBackupArchive;

it('encrypts production backup archives with AES-256 and can decrypt them', function () {
    $archivePath = tempnam(sys_get_temp_dir(), 'backup-encryption-');
    if ($archivePath === false) {
        throw new RuntimeException('Unable to create a temporary backup archive.');
    }

    $password = config('backup.backup.password');
    config()->set('backup.backup.password', 'test-backup-password');

    try {
        $zip = new ZipArchive;
        expect($zip->open($archivePath, ZipArchive::OVERWRITE))->toBeTrue();
        $zip->addFromString('db-dumps/database.sql.gz', 'test-dump');
        $zip->close();

        (new EncryptBackupArchive)->handle(new BackupZipWasCreated($archivePath));

        $zip = new ZipArchive;
        expect($zip->open($archivePath))->toBeTrue();
        if ($zip->getStream('db-dumps/database.sql.gz') !== false) {
            throw new RuntimeException('The test backup archive is not encrypted.');
        }

        $zip->setPassword('test-backup-password');
        $stream = $zip->getStream('db-dumps/database.sql.gz');
        if ($stream === false) {
            throw new RuntimeException('Unable to decrypt the test backup archive.');
        }

        expect(stream_get_contents($stream))->toBe('test-dump');
        fclose($stream);
        $zip->close();
    } finally {
        config()->set('backup.backup.password', $password);
        unlink($archivePath);
    }
});
