<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class MinioStorageService
{
    public function isConfigured(): bool
    {
        return (bool) (config('minio.access_key')
            && config('minio.secret_key')
            && config('minio.endpoint')
            && config('minio.bucket'));
    }

    public function usersPrefix(): string
    {
        return trim((string) config('minio.users_prefix', 'utilisateurs'), '/');
    }

    public function agentsDocumentsPrefix(): string
    {
        return trim((string) config('minio.agents_documents_prefix', 'agents/documents'), '/');
    }

    public function uploadAgentDocument(UploadedFile $file, int $agentId, string $type): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('MinIO n\'est pas configuré.');
        }

        $extension = $file->getClientOriginalExtension() ?: ($file->guessExtension() ?: 'bin');
        $extension = preg_replace('/[^a-z0-9]+/i', '', strtolower($extension)) ?: 'bin';
        $safeType = preg_replace('/[^a-z0-9_]+/i', '', $type);
        $objectKey = $this->agentsDocumentsPrefix().'/'.$agentId.'/'.$safeType.'_'.time().'.'.$extension;

        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Impossible de lire le fichier uploadé.');
        }

        $this->putObject($objectKey, $contents, $mimeType);

        return $objectKey;
    }

    public function isMinioObjectKey(?string $avatar): bool
    {
        if ($avatar === null || $avatar === '' || $avatar === 'default.jpg') {
            return false;
        }

        $prefix = $this->usersPrefix().'/';

        return str_starts_with($avatar, $prefix);
    }

    public function uploadUserPhoto(UploadedFile $file, int $userId): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('MinIO n\'est pas configuré.');
        }

        $extension = $file->guessExtension() ?: 'jpg';
        $objectKey = $this->usersPrefix().'/'.$userId.'_'.time().'.'.$extension;

        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('Impossible de lire le fichier uploadé.');
        }

        $this->putObject($objectKey, $contents, $mimeType);

        return $objectKey;
    }

    public function getObjectContents(string $objectKey): ?string
    {
        $url = $this->getPresignedUrl($objectKey, 300);

        if ($url === null) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $content === false) {
            return null;
        }

        return $content;
    }

    public function getPresignedUrl(string $objectKey, int $expires = 7200, ?string $bucket = null): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $accessKey = (string) config('minio.access_key');
        $secretKey = (string) config('minio.secret_key');
        $region = (string) config('minio.region', 'us-east-1');
        $bucket = $bucket ?? (string) config('minio.bucket');
        $endpoint = (string) config('minio.endpoint');

        $parsed = parse_url($endpoint);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 9000);
        $hostHeader = ($port == 80 || $port == 443) ? $host : "$host:$port";

        $service = 's3';
        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);
        $credentialScope = "$dateStamp/$region/$service/aws4_request";

        $encodedKey = implode('/', array_map('rawurlencode', explode('/', $objectKey)));
        $canonicalUri = '/'.$bucket.'/'.$encodedKey;
        $canonicalUri = str_replace('%2F', '/', $canonicalUri);

        $queryParams = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $accessKey.'/'.$credentialScope,
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => (string) $expires,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($queryParams);
        $canonicalQuery = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $canonicalHeaders = "host:$hostHeader\n";
        $signedHeaders = 'host';
        $payloadHash = 'UNSIGNED-PAYLOAD';

        $canonicalRequest = "GET\n$canonicalUri\n$canonicalQuery\n$canonicalHeaders\n$signedHeaders\n$payloadHash";
        $stringToSign = "AWS4-HMAC-SHA256\n$amzDate\n$credentialScope\n".hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4'.$secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $canonicalQuery .= '&X-Amz-Signature='.$signature;

        return $scheme.'://'.$hostHeader.$canonicalUri.'?'.$canonicalQuery;
    }

    private function putObject(string $objectKey, string $contents, string $mimeType): void
    {
        $accessKey = (string) config('minio.access_key');
        $secretKey = (string) config('minio.secret_key');
        $region = (string) config('minio.region', 'us-east-1');
        $bucket = (string) config('minio.bucket');
        $endpoint = (string) config('minio.endpoint');

        $parsed = parse_url($endpoint);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? 'localhost';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $hostHeader = $host.$port;

        $service = 's3';
        $now = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        $canonicalUri = '/'.$bucket.'/'.$objectKey;
        $payloadHash = hash('sha256', $contents);

        $headers = [
            'host' => $hostHeader,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $now,
            'content-type' => $mimeType,
            'content-length' => (string) strlen($contents),
        ];
        ksort($headers);

        $canonicalHeaders = '';
        $signedHeadersList = [];
        foreach ($headers as $key => $value) {
            $canonicalHeaders .= strtolower($key).':'.trim($value)."\n";
            $signedHeadersList[] = strtolower($key);
        }
        $signedHeaders = implode(';', $signedHeadersList);

        $canonicalRequest = "PUT\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

        $credentialScope = "{$date}/{$region}/{$service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$now}\n{$credentialScope}\n".hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $date, 'AWS4'.$secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorization = 'AWS4-HMAC-SHA256 Credential='.$accessKey.'/'.$credentialScope
            .', SignedHeaders='.$signedHeaders
            .', Signature='.$signature;

        $url = $scheme.'://'.$hostHeader.$canonicalUri;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $contents,
            CURLOPT_HTTPHEADER => [
                'Host: '.$hostHeader,
                'Content-Type: '.$mimeType,
                'Content-Length: '.strlen($contents),
                'x-amz-content-sha256: '.$payloadHash,
                'x-amz-date: '.$now,
                'Authorization: '.$authorization,
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException('Erreur MinIO : '.$curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('Erreur MinIO HTTP '.$httpCode.': '.$response);
        }
    }
}
