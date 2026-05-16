<?php
/**
 * Cloudflare R2 File Manager  (S3-compatible, AWS Signature V4)
 * ─────────────────────────────────────────────────────────────
 * Public API:
 *
 *   uploadToR2(array $file, string $folder, string $fileType = 'IMAGE'): array
 *       Validates, uploads $_FILES-style $file to R2 under $folder/.
 *       Returns: ['success'=>true, 'url'=>'https://…', 'key'=>'folder/name.ext']
 *              | ['success'=>false, 'error'=>'…']
 *
 *   deleteFromR2(string $fileUrlOrKey): bool
 *       Deletes the object at the given public URL or object key.
 *       Returns true on success or if file did not exist (idempotent).
 *
 * Usage example in service.php:
 *   require_once __DIR__.'/../common/uploadFileCloudflare.php';
 *
 *   // Upload
 *   $result = uploadToR2($_FILES['brochure'], 'products/brochures', 'PDF');
 *   if (!$result['success']) { /* show error * / }
 *   $fileUrl = $result['url'];
 *
 *   // Delete old file before saving new one
 *   if ($oldUrl) deleteFromR2($oldUrl);
 *
 * Accepted $fileType values (case-insensitive):
 *   'IMAGE'  → jpg, jpeg, png, gif, webp
 *   'JPG'    → jpg, jpeg
 *   'PNG'    → png
 *   'PDF'    → pdf
 *   'DOC'    → doc, docx
 *   'EXCEL'  → xls, xlsx, csv
 *   'VIDEO'  → mp4, avi, mov, mkv, webm
 *   'ANY'    → no extension restriction (size limit still applies)
 *   or any comma-separated list of extensions, e.g. 'pdf,docx'
 */

declare(strict_types=1);

if (!function_exists('sinelec_env')) {
    require_once __DIR__ . '/functions.php';
}

/* ═══════════════════════════════════════════════════════════════════
   INTERNAL CONFIG  — loaded once from .env
═══════════════════════════════════════════════════════════════════ */
function _r2_config(): array
{
    static $cfg = null;
    if ($cfg !== null) return $cfg;

    $cfg = [
        'account_id'  => sinelec_env('R2_ACCOUNT_ID', ''),
        'access_key'  => sinelec_env('R2_ACCESS_KEY', ''),
        'secret_key'  => sinelec_env('R2_SECRET_KEY', ''),
        'bucket'      => sinelec_env('BUCKET_NAME', 'sinlect-docs'),
        'region'      => sinelec_env('REGION', 'auto'),
        'public_base' => rtrim(sinelec_env('PUBLIC_BASE_URL', ''), '/'),
        'endpoint'    => sinelec_env('R2_END_POINT', ''),
    ];

    /* R2 endpoint: prefer explicit R2_END_POINT, fall back to standard pattern */
    if (empty($cfg['endpoint']) && $cfg['account_id']) {
        $cfg['endpoint'] = 'https://'.$cfg['account_id'].'.r2.cloudflarestorage.com';
    }
    $cfg['endpoint'] = rtrim($cfg['endpoint'], '/');

    return $cfg;
}

/* ═══════════════════════════════════════════════════════════════════
   ALLOWED EXTENSION MAP
═══════════════════════════════════════════════════════════════════ */
function _r2_allowed_extensions(string $fileType): array
{
    $presets = [
        'IMAGE'  => ['jpg','jpeg','png','gif','webp'],
        'JPG'    => ['jpg','jpeg'],
        'PNG'    => ['png'],
        'PDF'    => ['pdf'],
        'DOC'    => ['doc','docx'],
        'EXCEL'  => ['xls','xlsx','csv'],
        'VIDEO'  => ['mp4','avi','mov','mkv','webm'],
        'ANY'    => [],   /* empty = allow all */
    ];

    $key = strtoupper(trim($fileType));
    if (isset($presets[$key])) return $presets[$key];

    /* Treat as comma-separated list: 'pdf,docx' */
    return array_filter(array_map('trim', explode(',', strtolower($fileType))));
}

/* ═══════════════════════════════════════════════════════════════════
   MIME TYPE MAP
═══════════════════════════════════════════════════════════════════ */
function _r2_mime(string $ext): string
{
    $map = [
        'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png'  => 'image/png',  'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'csv'  => 'text/csv',
        'mp4'  => 'video/mp4',  'avi'  => 'video/x-msvideo',
        'mov'  => 'video/quicktime', 'mkv' => 'video/x-matroska',
        'webm' => 'video/webm',
    ];
    return $map[strtolower($ext)] ?? 'application/octet-stream';
}

/* ═══════════════════════════════════════════════════════════════════
   AWS SIGNATURE V4  —  pure PHP, no SDK
═══════════════════════════════════════════════════════════════════ */

/**
 * Build Authorization header + required signed headers for an S3-style request.
 * Returns array of HTTP headers to merge into the cURL request.
 */
function _r2_sign_request(
    string $method,
    string $objectKey,     /* e.g. "products/file.pdf" */
    string $contentType,
    string $bodyHash,      /* sha256 hex of request body; 'UNSIGNED-PAYLOAD' for streaming */
    int    $contentLength = 0
): array {
    $cfg        = _r2_config();
    $host       = parse_url($cfg['endpoint'], PHP_URL_HOST);
    $region     = $cfg['region'];
    $service    = 's3';
    $accessKey  = $cfg['access_key'];
    $secretKey  = $cfg['secret_key'];

    $now        = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $dateStamp  = $now->format('Ymd');
    $amzDate    = $now->format('Ymd\THis\Z');

    /* ── Canonical headers (must be sorted, lowercase) ── */
    $headers = [
        'content-type'        => $contentType,
        'host'                => $host,
        'x-amz-content-sha256'=> $bodyHash,
        'x-amz-date'          => $amzDate,
    ];
    if ($contentLength > 0) {
        $headers['content-length'] = (string)$contentLength;
    }
    ksort($headers);

    $canonicalHeaders = '';
    $signedHeadersList = [];
    foreach ($headers as $k => $v) {
        $canonicalHeaders  .= $k.':'.$v."\n";
        $signedHeadersList[] = $k;
    }
    $signedHeaders = implode(';', $signedHeadersList);

    /* ── Canonical request ── */
    $canonicalUri     = '/'.ltrim($objectKey, '/');
    $canonicalQuery   = '';
    $canonicalRequest = implode("\n", [
        strtoupper($method),
        $canonicalUri,
        $canonicalQuery,
        $canonicalHeaders,
        $signedHeaders,
        $bodyHash,
    ]);

    /* ── String to sign ── */
    $credentialScope = "$dateStamp/$region/$service/aws4_request";
    $stringToSign    = implode("\n", [
        'AWS4-HMAC-SHA256',
        $amzDate,
        $credentialScope,
        hash('sha256', $canonicalRequest),
    ]);

    /* ── Signing key ── */
    $signingKey = hash_hmac('sha256', 'aws4_request',
                    hash_hmac('sha256', $service,
                      hash_hmac('sha256', $region,
                        hash_hmac('sha256', $dateStamp, 'AWS4'.$secretKey, true),
                      true),
                    true),
                  true);

    $signature = hash_hmac('sha256', $stringToSign, $signingKey);

    /* ── Authorization header ── */
    $authorization = "AWS4-HMAC-SHA256 Credential=$accessKey/$credentialScope, "
                   . "SignedHeaders=$signedHeaders, Signature=$signature";

    /* Return all headers the caller must send */
    $result = [
        'Authorization'        => $authorization,
        'Content-Type'         => $contentType,
        'x-amz-date'           => $amzDate,
        'x-amz-content-sha256' => $bodyHash,
        'Host'                 => $host,
    ];
    if ($contentLength > 0) {
        $result['Content-Length'] = (string)$contentLength;
    }
    return $result;
}

/**
 * Execute a signed request against R2 using cURL.
 * Returns ['status'=>int, 'body'=>string, 'error'=>string|null].
 */
function _r2_curl_request(
    string $method,
    string $objectKey,
    string $contentType  = '',
    string $body         = '',
    bool   $bodyIsFile   = false,  /* if true, $body is a local file path */
    int    $bodyLength   = 0
): array {
    $cfg      = _r2_config();
    $url      = $cfg['endpoint'].'/'.$cfg['bucket'].'/'.ltrim($objectKey, '/');
    $bodyHash = $bodyIsFile
        ? hash_file('sha256', $body)
        : hash('sha256', $body);

    $headers = _r2_sign_request(
        $method, $cfg['bucket'].'/'.ltrim($objectKey, '/'),
        $contentType, $bodyHash,
        $bodyIsFile ? ($bodyLength ?: (int)filesize($body)) : strlen($body)
    );

    $headerLines = [];
    foreach ($headers as $k => $v) {
        $headerLines[] = "$k: $v";
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_HTTPHEADER     => $headerLines,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($bodyIsFile) {
            $fh = fopen($body, 'rb');
            curl_setopt($ch, CURLOPT_PUT,        true);
            curl_setopt($ch, CURLOPT_INFILE,     $fh);
            curl_setopt($ch, CURLOPT_INFILESIZE, $headers['Content-Length'] ?? filesize($body));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response   = curl_exec($ch);
    $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch) ?: null;
    curl_close($ch);

    if (isset($fh) && is_resource($fh)) fclose($fh);

    return ['status' => $httpStatus, 'body' => (string)$response, 'error' => $curlError];
}

/* ═══════════════════════════════════════════════════════════════════
   PUBLIC API
═══════════════════════════════════════════════════════════════════ */

/**
 * Upload a file to Cloudflare R2.
 *
 * @param array  $file      $_FILES['fieldname'] entry
 * @param string $folder    Destination prefix, e.g. 'products/brochures'
 * @param string $fileType  Allowed type: 'IMAGE'|'JPG'|'PDF'|'DOC'|'VIDEO'|'ANY'
 *                          or comma-separated extensions: 'pdf,docx'
 * @param int    $maxMB     Maximum file size in MB (default 20)
 *
 * @return array  ['success'=>true,  'url'=>string, 'key'=>string]
 *              | ['success'=>false, 'error'=>string]
 */
function uploadToR2(array $file, string $folder, string $fileType = 'IMAGE', int $maxMB = 20): array
{
    /* ── 1. Basic file presence check ── */
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded or invalid upload.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errMsg = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        ];
        return ['success' => false, 'error' => $errMsg[$file['error']] ?? 'Unknown upload error.'];
    }

    /* ── 2. Size check ── */
    $maxBytes = $maxMB * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'error' => "File exceeds maximum size of {$maxMB}MB."];
    }

    /* ── 3. Extension check ── */
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = _r2_allowed_extensions($fileType);
    if (!empty($allowed) && !in_array($ext, $allowed, true)) {
        return [
            'success' => false,
            'error'   => 'Invalid file type. Allowed: '.implode(', ', array_map('strtoupper', $allowed)).'.',
        ];
    }

    /* ── 4. MIME sniff for extra safety (images & PDFs) ── */
    if (function_exists('finfo_open')) {
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $safeMimes = [
            'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
            'gif'=>'image/gif','webp'=>'image/webp','pdf'=>'application/pdf',
        ];
        if (isset($safeMimes[$ext]) && $safeMimes[$ext] !== $mimeReal) {
            return ['success' => false, 'error' => 'File content does not match its extension.'];
        }
    }

    /* ── 5. Build unique object key ── */
    $uniqueName  = date('YmdHis').'_'.bin2hex(random_bytes(6)).'.'.$ext;
    $folder      = trim($folder, '/');
    $objectKey   = $folder !== '' ? $folder.'/'.$uniqueName : $uniqueName;
    $contentType = _r2_mime($ext);

    /* ── 6. PUT to R2 ── */
    $result = _r2_curl_request('PUT', $objectKey, $contentType, $file['tmp_name'], true, (int)$file['size']);

    if ($result['error'] !== null) {
        error_log("R2 upload cURL error [{$objectKey}]: ".$result['error']);
        return ['success' => false, 'error' => 'Network error while uploading file.'];
    }

    /* R2 returns 200 or 204 on success */
    if (!in_array($result['status'], [200, 204], true)) {
        error_log("R2 upload HTTP {$result['status']} [{$objectKey}]: ".$result['body']);
        return ['success' => false, 'error' => 'Storage service returned error '.$result['status'].'.'];
    }

    $cfg       = _r2_config();
    $publicUrl = $cfg['public_base'].'/'.$objectKey;

    return ['success' => true, 'url' => $publicUrl, 'key' => $objectKey];
}

/**
 * Delete a file from Cloudflare R2.
 *
 * Accepts either:
 *   - The full public URL:  'https://cdn.sinelec-tech.com/products/file.pdf'
 *   - The object key only:  'products/file.pdf'
 *
 * Returns true on success or when the file simply does not exist (idempotent).
 * Returns false only on a real error (network, auth, etc.)
 */
function deleteFromR2(string $fileUrlOrKey): bool
{
    if (trim($fileUrlOrKey) === '') return true;

    $cfg = _r2_config();

    /* Extract the object key from a full URL */
    $objectKey = $fileUrlOrKey;
    if (str_starts_with($fileUrlOrKey, 'http')) {
        $base = $cfg['public_base'].'/';
        if (str_starts_with($fileUrlOrKey, $base)) {
            $objectKey = substr($fileUrlOrKey, strlen($base));
        } else {
            /* Try parsing it as a URL and taking the path */
            $path      = ltrim(parse_url($fileUrlOrKey, PHP_URL_PATH) ?? '', '/');
            $objectKey = $path;
        }
    }
    $objectKey = ltrim($objectKey, '/');

    if ($objectKey === '') return true;

    $result = _r2_curl_request('DELETE', $objectKey, '', '', false, 0);

    if ($result['error'] !== null) {
        error_log("R2 delete cURL error [{$objectKey}]: ".$result['error']);
        return false;
    }

    /* 204 = deleted, 404 = already gone — both are acceptable */
    if (in_array($result['status'], [204, 404], true)) {
        return true;
    }

    error_log("R2 delete HTTP {$result['status']} [{$objectKey}]: ".$result['body']);
    return false;
}

/**
 * Helper: replace an existing R2 file.
 * Uploads the new file first; only deletes the old one on success.
 * This prevents data loss if the upload fails.
 *
 * @param array       $newFile     $_FILES['fieldname']
 * @param string      $folder      Destination prefix
 * @param string      $fileType    Allowed type
 * @param string|null $oldFileUrl  Existing file URL/key to delete on success
 * @param int         $maxMB       Max file size
 *
 * @return array ['success'=>true, 'url'=>string, 'key'=>string]
 *             | ['success'=>false, 'error'=>string]
 */
function replaceR2File(array $newFile, string $folder, string $fileType = 'IMAGE', ?string $oldFileUrl = null, int $maxMB = 20): array
{
    $result = uploadToR2($newFile, $folder, $fileType, $maxMB);

    /* Delete old file only after confirmed successful upload */
    if ($result['success'] && !empty($oldFileUrl)) {
        deleteFromR2($oldFileUrl);
    }

    return $result;
}
