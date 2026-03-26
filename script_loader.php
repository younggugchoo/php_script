<?php
/**
 * 스크립트 파일 로더
 * - WebVTT (.vtt): HH:MM:SS.mmm --> HH:MM:SS.mmm + 텍스트 (sample.vtt 형식)
 * 실패 시 sample_script.php
 */

/** WebVTT 시간 (HH:MM:SS.mmm) 를 초로 변환 */
function parse_time_vtt($s) {
    $s = trim($s);
    if (preg_match('/^(\d+):(\d+):(\d+)\.(\d+)$/', $s, $m)) {
        return (int)$m[1] * 3600 + (int)$m[2] * 60 + (int)$m[3] + (int)$m[4] / 1000;
    }
    if (preg_match('/^(\d+):(\d+)\.(\d+)$/', $s, $m)) {
        return (int)$m[1] * 60 + (int)$m[2] + (int)$m[3] / 1000;
    }
    return 0.0;
}

/** WebVTT 형식 파싱 (sample.vtt 구조) */
function parse_vtt($content) {
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $segments = [];
    $i = 0;
    $len = count($lines);
    while ($i < $len) {
        $line = trim($lines[$i]);
        $i++;
        if ($line === '' || $line === 'WEBVTT') continue;
        if (preg_match('/^(\d{2}:\d{2}:\d{2}\.\d{3}|\d{2}:\d{2}\.\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2}\.\d{3}|\d{2}:\d{2}\.\d{3})/', $line, $m)) {
            $start = parse_time_vtt($m[1]);
            $end = parse_time_vtt($m[2]);
            $textLines = [];
            while ($i < $len) {
                $next = trim($lines[$i]);
                if ($next === '') break;
                $textLines[] = $next;
                $i++;
            }
            $text = implode(' ', $textLines);
            if ($text !== '') {
                $segments[] = ['start' => $start, 'end' => $end, 'text' => $text];
            }
        }
    }
    return $segments;
}

/** 화면 표시용 시간 포맷: 60분 미만 mm:ss, 60분 이상 hh:mm:ss */
function format_display_time($seconds) {
    $s = (float) $seconds;
    $h = (int) floor($s / 3600);
    $m = (int) floor(fmod($s, 3600) / 60);
    $sec = (int) floor(fmod($s, 60));
    if ($h >= 1) {
        return sprintf('%d:%02d:%02d', $h, $m, $sec);
    }
    return sprintf('%d:%02d', $m, $sec);
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
 * 영상명 스크립트 로드. .vtt 파일만 사용.
 * 기본(videoplayback)만 실패 시 sample_script.php 사용.
 */
function load_script($scriptFilename = null) {
    if ($scriptFilename === null || $scriptFilename === '') {
        $scriptFilename = 'txt/videoplayback.vtt';
    }
    if (strpos($scriptFilename, '..') !== false || preg_match('/[^a-zA-Z0-9_.\-\/\\\\]/', $scriptFilename)) {
        return [];
    }
    $base = preg_replace('/\.vtt$/i', '', $scriptFilename);
    $path = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base . '.vtt');
    $content = is_file($path) && is_readable($path) ? read_script_file($path) : null;
    if ($content !== null && $content !== '') {
        $segments = parse_vtt($content);
        if (!empty($segments)) return $segments;
    }
    $baseName = basename($base);
    $isDefault = ($baseName === 'videoplayback');
    return $isDefault ? require __DIR__ . '/sample_script.php' : [];
}
