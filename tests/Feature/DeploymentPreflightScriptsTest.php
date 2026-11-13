<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

it('accepts encrypted database backups and rejects unencrypted archives', function () {
    $root = sys_get_temp_dir().'/backup-verifier-'.bin2hex(random_bytes(8));
    $backupDirectory = $root.'/Followme';
    $archivePath = $backupDirectory.'/backup.zip';
    $passwordPath = $root.'/password';
    $password = bin2hex(random_bytes(32));

    expect(mkdir($backupDirectory, 0700, true))->toBeTrue();
    file_put_contents($passwordPath, $password);

    try {
        $zip = new ZipArchive;
        expect($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();
        $zip->addFromString('db-dumps/database.sql.gz', 'test-dump');
        expect($zip->setPassword($password))->toBeTrue();
        expect($zip->setEncryptionIndex(0, ZipArchive::EM_AES_256))->toBeTrue();
        expect($zip->close())->toBeTrue();

        $process = new Process([PHP_BINARY, base_path('scripts/verify-encrypted-backup.php')], null, [
            'PREDEPLOY_BACKUP_ROOT' => $root,
            'BACKUP_ARCHIVE_PASSWORD_FILE' => $passwordPath,
        ]);
        $process->run();
        expect($process->isSuccessful())->toBeTrue();
        expect($process->getOutput())->toContain('backup_verified encrypted=yes');

        $zip = new ZipArchive;
        expect($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();
        $zip->addFromString('db-dumps/database.sql.gz', 'test-dump');
        expect($zip->close())->toBeTrue();

        $process->run();
        expect($process->isSuccessful())->toBeFalse();

        $zip = new ZipArchive;
        expect($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE))->toBeTrue();
        $zip->addFromString('db-dumps/database.sql.gz', '');
        expect($zip->setPassword($password))->toBeTrue();
        expect($zip->setEncryptionIndex(0, ZipArchive::EM_AES_256))->toBeTrue();
        expect($zip->close())->toBeTrue();

        $process = new Process([PHP_BINARY, base_path('scripts/verify-encrypted-backup.php')], null, [
            'PREDEPLOY_BACKUP_ROOT' => $root,
            'BACKUP_ARCHIVE_PASSWORD_FILE' => $passwordPath,
        ]);
        $process->run();
        expect($process->isSuccessful())->toBeFalse();
    } finally {
        File::deleteDirectory($root);
    }
});

it('validates Firebase service-account configuration without exposing credentials', function () {
    $root = sys_get_temp_dir().'/firebase-verifier-'.bin2hex(random_bytes(8));
    expect(mkdir($root, 0700, true))->toBeTrue();

    try {
        $privateKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        expect($privateKey)->not->toBeFalse();
        expect(openssl_pkey_export($privateKey, $privateKeyPem))->toBeTrue();

        $credentialsPath = $root.'/service-account.json';
        file_put_contents($credentialsPath, json_encode([
            'project_id' => 'test-project',
            'client_email' => 'push@test-project.iam.gserviceaccount.com',
            'private_key' => $privateKeyPem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_THROW_ON_ERROR));

        $process = new Process([PHP_BINARY, base_path('scripts/verify-firebase-credentials.php')], null, [
            'FIREBASE_PROJECT_ID' => 'test-project',
            'FIREBASE_CREDENTIALS' => $credentialsPath,
        ]);
        $process->run();
        expect($process->isSuccessful())->toBeTrue();
        expect($process->getOutput())->toContain('firebase_credentials_verified');

        $process = new Process([PHP_BINARY, base_path('scripts/verify-firebase-credentials.php')], null, [
            'FIREBASE_PROJECT_ID' => 'different-project',
            'FIREBASE_CREDENTIALS' => $credentialsPath,
        ]);
        $process->run();
        expect($process->isSuccessful())->toBeFalse();
    } finally {
        File::deleteDirectory($root);
    }
});
