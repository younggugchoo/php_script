<?php
/**
 * 스크립트 구간 데이터 (JSON 출력)
 * videoplayback.txt → 실패 시 sample_script.php
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/script_loader.php';
$scriptSegments = load_script();
echo json_encode($scriptSegments, JSON_UNESCAPED_UNICODE);
