<?php
/**
 * 비디오 파일 스트리밍 (Range 요청 지원)
 * PHP 내장 서버는 Range를 처리하지 않아 seek가 안 되므로, 이 스크립트로 재생 시 seek 가능.
 * 사용: video.php?f=videoplayback.mp4
 */
$baseDir = __DIR__ . DIRECTORY_SEPARATOR;
$file = isset($_GET['f']) ? $_GET['f'] : '';
if ($file === '' || preg_match('/[^a-zA-Z0-9_.-]/', $file)) {
    http_response_code(400);
    exit('Bad request');
}
$path = realpath($baseDir . $file);
if ($path === false || !is_file($path) || strpos($path, $baseDir) !== 0) {
    http_response_code(404);
    exit('Not found');
}

$size = filesize($path);
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = 'video/mp4';
if ($ext === 'webm') $mime = 'video/webm';
elseif ($ext === 'ogg') $mime = 'video/ogg';

header('Accept-Ranges: bytes');
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=3600');

$range = isset($_SERVER['HTTP_RANGE']) ? trim($_SERVER['HTTP_RANGE']) : '';
if ($range === '') {
    header('Content-Length: ' . $size);
    header('HTTP/1.1 200 OK');
    readfile($path);
    exit;
}

if (!preg_match('/^bytes=(\d*)-(\d*)$/', $range, $m)) {
    header('HTTP/1.1 416 Range Not Satisfiable');
    header('Content-Range: bytes */' . $size);
    exit;
}
$start = $m[1] === '' ? 0 : (int) $m[1];
$end   = $m[2] === '' ? $size - 1 : (int) $m[2];
if ($start > $end || $end >= $size) {
    header('HTTP/1.1 416 Range Not Satisfiable');
    header('Content-Range: bytes */' . $size);
    exit;
}
$length = $end - $start + 1;
header('HTTP/1.1 206 Partial Content');
header('Content-Length: ' . $length);
header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);

$fp = fopen($path, 'rb');
if ($fp === false) {
    http_response_code(500);
    exit;
}
fseek($fp, $start, SEEK_SET);
$buf = 8192;
$rem = $length;
while ($rem > 0 && !feof($fp)) {
    $read = $rem > $buf ? $buf : $rem;
    echo fread($fp, $read);
    $rem -= $read;
}
fclose($fp);
