<?php

$projectId = getenv('FIREBASE_PROJECT_ID');
$credentialsPath = getenv('FIREBASE_CREDENTIALS');

if (! is_string($projectId) || $projectId === '' || ! is_string($credentialsPath) || $credentialsPath === '') {
    throw new RuntimeException('Firebase project ID or service-account credentials are not configured.');
}

$contents = file_get_contents($credentialsPath);
if ($contents === false) {
    throw new RuntimeException('Firebase service-account credentials are not readable.');
}

try {
    $credentials = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    throw new RuntimeException('Firebase service-account credentials are invalid JSON.', previous: $exception);
}

if (! is_array($credentials)
    || ($credentials['project_id'] ?? null) !== $projectId
    || ! is_string($credentials['client_email'] ?? null)
    || ! is_string($credentials['private_key'] ?? null)
    || ($credentials['token_uri'] ?? null) !== 'https://oauth2.googleapis.com/token') {
    throw new RuntimeException('Firebase service-account credentials are missing required fields or target a different project.');
}

if (openssl_pkey_get_private($credentials['private_key']) === false) {
    throw new RuntimeException('Firebase service-account private key is invalid.');
}

echo "firebase_credentials_verified project_id_present=yes service_account=yes private_key=yes\n";
