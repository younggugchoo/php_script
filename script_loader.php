<?php
/**
 * 스크립트 파일 로더
 * 영상명.txt (시간줄 M:SS + 텍스트줄) → 실패 시 sample_script.php
 */

/** M:SS 또는 H:MM:SS 를 초로 변환 */
function parse_time_simple($s) {
    $s = trim($s);
    if (preg_match('/^(\d+):(\d+):(\d+)$/', $s, $m)) {
        return (int)$m[1] * 3600 + (int)$m[2] * 60 + (int)$m[3];
    }
    if (preg_match('/^(\d+):(\d+)$/', $s, $m)) {
        return (int)$m[1] * 60 + (int)$m[2];
    }
    return 0.0;
}

/** 한 줄에 시간(M:SS), 다음 줄에 텍스트 형식 파싱 */
function parse_txt_script($content) {
    $lines = preg_split('/\r\n|\r|\n/', trim(str_replace("\r\n", "\n", $content)));
    $segments = [];
    $i = 0;
    $defaultEndGap = 5;
    while ($i < count($lines)) {
        $line = trim($lines[$i]);
        if ($line === '' || !preg_match('/^\d+:\d+(:\d+)?$/', $line)) {
            $i++;
            continue;
        }
        $start = parse_time_simple($line);
        $i++;
        $text = ($i < count($lines)) ? trim($lines[$i]) : '';
        $i++;
        if ($text === '') continue;
        $end = $start + $defaultEndGap;
        if ($i < count($lines) && preg_match('/^\d+:\d+(:\d+)?$/', trim($lines[$i]))) {
            $end = parse_time_simple(trim($lines[$i]));
        }
        $segments[] = ['start' => $start, 'end' => $end, 'text' => $text];
    }
    return $segments;
}

/** 파일 읽기 + UTF-8 인코딩 보정 */
function read_script_file($path) {
    if (!is_file($path) || !is_readable($path)) return null;
    $content = file_get_contents($path);
    if ($content === false) return null;
    if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
        $enc = mb_detect_encoding($content, ['UTF-8', 'EUC-KR', 'CP949'], true);
        if ($enc && $enc !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $enc);
        }
    }
    return $content;
}

/**
 * 영상명.txt 로드.
 * 기본(videoplayback.txt)만 실패 시 sample_script.php 사용.
 * 그 외 영상명은 파일 없음/비어있으면 빈 배열 반환.
 */
function load_script($scriptFilename = null) {
    if ($scriptFilename === null || $scriptFilename === '') {
        $scriptFilename = 'videoplayback.txt';
    }
    if (strpos($scriptFilename, '..') !== false || preg_match('/[^a-zA-Z0-9_.\-\/\\\\]/', $scriptFilename)) {
        return [];
    }
    $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $scriptFilename);
    $content = read_script_file($path);
    if ($content !== null) {
        $segments = parse_txt_script($content);
        if (!empty($segments)) return $segments;
    }
    $isDefault = (basename($scriptFilename) === 'videoplayback.txt');
    return $isDefault ? require __DIR__ . '/sample_script.php' : [];
}
