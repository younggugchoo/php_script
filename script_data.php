<?php
/**
 * 스크립트 구간 데이터 (JSON 출력)
 * videoplayback.txt → videoplayback.srt → sample_script.php 순으로 사용.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/srt_loader.php';
$scriptSegments = load_script();
echo json_encode($scriptSegments, JSON_UNESCAPED_UNICODE);
