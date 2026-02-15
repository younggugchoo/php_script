<?php
/**
 * 스크립트 데이터: videoplayback_script.json 로드 (txt/srt 실패 시 fallback용)
 * JSON이 없거나 실패 시 아래 기본 데이터 사용.
 */
$jsonPath = __DIR__ . DIRECTORY_SEPARATOR . 'videoplayback_script.json';
if (is_file($jsonPath) && is_readable($jsonPath)) {
    $json = file_get_contents($jsonPath);
    if ($json !== false) {
        $data = json_decode($json, true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }
}
return [
    ['start' => 0,   'end' => 4,   'text' => '안녕하세요. 이 영상은 스크립트 동기화 데모용 샘플입니다.'],
    ['start' => 4,   'end' => 8,   'text' => '재생을 시작하면 오른쪽 스크립트가 현재 구간에 맞춰 자동으로 스크롤되고 하이라이트 됩니다.'],
    ['start' => 8,   'end' => 12,  'text' => '각 문장은 시작 시간과 종료 시간으로 구간이 정해져 있어요.'],
    ['start' => 12,  'end' => 16,  'text' => '스크립트 문장을 클릭하면 해당 위치로 영상이 이동합니다.'],
    ['start' => 16,  'end' => 21,  'text' => 'PHP에서 구간 데이터를 불러오고, 브라우저에서는 재생 시간에 맞춰 현재 문장을 갱신합니다.'],
    ['start' => 21,  'end' => 25,  'text' => '실제 서비스에서는 자막 파일이나 DB에 저장된 스크립트를 사용할 수 있어요.'],
    ['start' => 25,  'end' => 30,  'text' => '감사합니다. 끝까지 시청해 주셔서 감사합니다.'],
];
