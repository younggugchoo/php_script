<?php
/**
 * 스크립트 파일 로더
 * - videoplayback.txt: 시간줄(M:SS) + 텍스트줄 형식
 * - videoplayback.srt: 표준 SRT 형식
 * [ start, end, text ] 배열로 반환
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

/** videoplayback.txt 형식: 한 줄에 시간(M:SS), 다음 줄에 텍스트 */
function parse_txt_script($content) {
    $content = str_replace("\r\n", "\n", $content);
    $lines = preg_split('/\r\n|\r|\n/', trim($content));
    $segments = [];
    $i = 0;
    $defaultEndGap = 5;
    while ($i < count($lines)) {
        $line = trim($lines[$i]);
        if ($line === '') {
            $i++;
            continue;
        }
        if (!preg_match('/^\d+:\d+(:\d+)?$/', $line)) {
            $i++;
            continue;
        }
        $start = parse_time_simple($line);
        $i++;
        $text = ($i < count($lines)) ? trim($lines[$i]) : '';
        $i++;
        if ($text === '') continue;
        $end = $start + $defaultEndGap;
        if ($i < count($lines)) {
            $next = trim($lines[$i]);
            if (preg_match('/^\d+:\d+(:\d+)?$/', $next)) {
                $end = parse_time_simple($next);
            }
        }
        $segments[] = ['start' => $start, 'end' => $end, 'text' => $text];
    }
    return $segments;
}

function srt_time_to_seconds($s) {
    $s = trim($s);
    if (preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})[,.](\d{1,3})$/', $s, $m)) {
        return (int)$m[1] * 3600 + (int)$m[2] * 60 + (int)$m[3] + (int)str_pad($m[4], 3, '0') / 1000;
    }
    return 0.0;
}

function parse_srt($content) {
    $content = str_replace("\r\n", "\n", $content);
    $content = trim($content);
    $blocks = preg_split('/\n\s*\n/', $content, -1, PREG_SPLIT_NO_EMPTY);
    $segments = [];

    foreach ($blocks as $block) {
        $lines = explode("\n", trim($block));
        if (count($lines) < 2) continue;

        $first = trim($lines[0]);
        $second = trim($lines[1]);

        // 두 번째 줄이 타임라인 (00:00:00,000 --> 00:00:00,000)
        if (preg_match('/^(\d{1,2}:\d{1,2}:\d{1,2}[,.]\d{1,3})\s*-->\s*(\d{1,2}:\d{1,2}:\d{1,2}[,.]\d{1,3})\s*/', $second, $m)) {
            $start = srt_time_to_seconds($m[1]);
            $end   = srt_time_to_seconds($m[2]);
            $text  = implode(' ', array_slice($lines, 2));
            $text  = trim(preg_replace('/\s+/', ' ', $text));
            if ($text !== '') {
                $segments[] = ['start' => $start, 'end' => $end, 'text' => $text];
            }
            continue;
        }

        // 첫 번째 줄이 타임라인인 경우 (번호 없이)
        if (preg_match('/^(\d{1,2}:\d{1,2}:\d{1,2}[,.]\d{1,3})\s*-->\s*(\d{1,2}:\d{1,2}:\d{1,2}[,.]\d{1,3})\s*/', $first, $m)) {
            $start = srt_time_to_seconds($m[1]);
            $end   = srt_time_to_seconds($m[2]);
            $text  = implode(' ', array_slice($lines, 1));
            $text  = trim(preg_replace('/\s+/', ' ', $text));
            if ($text !== '') {
                $segments[] = ['start' => $start, 'end' => $end, 'text' => $text];
            }
        }
    }

    return $segments;
}

/**
 * 파일 내용 읽기 + 인코딩 보정 (UTF-8)
 */
function read_script_file($path) {
    if (!is_file($path) || !is_readable($path)) return null;
    $content = file_get_contents($path);
    if ($content === false) return null;
    if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'EUC-KR', 'CP949'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
    }
    return $content;
}

/**
 * videoplayback.txt 로드 (시간줄+텍스트줄 형식). 실패 시 null.
 */
function load_script_from_txt($txtPath = null) {
    if ($txtPath === null) {
        $txtPath = __DIR__ . DIRECTORY_SEPARATOR . 'videoplayback.txt';
    }
    $content = read_script_file($txtPath);
    if ($content === null) return null;
    $segments = parse_txt_script($content);
    return !empty($segments) ? $segments : null;
}

/**
 * videoplayback.srt 로드 (표준 SRT 형식). 실패 시 null.
 */
function load_script_from_srt($srtPath = null) {
    if ($srtPath === null) {
        $srtPath = __DIR__ . DIRECTORY_SEPARATOR . 'videoplayback.srt';
    }
    $content = read_script_file($srtPath);
    if ($content === null) return null;
    $segments = parse_srt($content);
    return !empty($segments) ? $segments : null;
}

/**
 * 스크립트 로드: videoplayback.txt → videoplayback.srt → sample_script.php 순으로 시도
 */
function load_script() {
    $segments = load_script_from_txt();
    if ($segments !== null) return $segments;
    $segments = load_script_from_srt();
    if ($segments !== null) return $segments;
    return require __DIR__ . '/sample_script.php';
}
