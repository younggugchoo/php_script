<?php
/**
 * 유튜브 스타일 스크립트 동기화 페이지
 * 영상 재생 위치에 맞춰 스크립트 레이어 자동 스크롤 및 포커싱
 * 사용: index.php?v=영상명 → 영상명.mp4, 영상명.vtt 로드
 */
$videoName = isset($_GET['v']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['v']) : '';
if ($videoName === '') {
    $videoName = 'videoplayback';
}
$videoFile = 'video/' . $videoName . '.mp4';
$scriptFile = 'txt/' . $videoName . '.vtt';

require_once __DIR__ . '/script_loader.php';
$scriptSegments = load_script($scriptFile);
$videoDuration = !empty($scriptSegments) ? (float) end($scriptSegments)['end'] : 30;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>영상 스크립트 동기화</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', 'Malgun Gothic', sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
        }
        .container {
            display: flex;
            flex-direction: column;
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px;
            gap: 16px;
        }
        @media (min-width: 900px) {
            .container { flex-direction: row; }
        }
        .video-wrap {
            flex: 1;
            min-width: 0;
        }
        .video-wrap {
            position: relative;
        }
        .video-wrap video {
            width: 100%;
            border-radius: 8px;
            background: #000;
        }
        .script-toggle {
            position: absolute;
            bottom: 10px;
            left: 10px;
            z-index: 10;
            padding: 6px 12px;
            font-size: 0.85rem;
            color: #fff;
            background: rgba(0,0,0,0.65);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .script-toggle:hover {
            background: rgba(0,0,0,0.85);
        }
        .script-panel {
            flex: 0 0 380px;
            display: flex;
            flex-direction: column;
            background: #252525;
            border-radius: 8px;
            overflow: hidden;
            max-height: 50vh;
            min-height: 200px;
            transition: opacity 0.2s, margin 0.2s;
        }
        .script-panel.hidden {
            display: none !important;
        }
        @media (min-width: 900px) {
            .script-panel { max-height: 75vh; min-height: 280px; }
        }
        .script-panel h3 {
            margin: 0;
            padding: 12px 16px;
            font-size: 1rem;
            background: #2d2d2d;
            border-bottom: 1px solid #3a3a3a;
            flex-shrink: 0;
        }
        .script-list {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        .script-list::-webkit-scrollbar {
            width: 8px;
        }
        .script-list::-webkit-scrollbar-track {
            background: #1e1e1e;
            border-radius: 4px;
        }
        .script-list::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        .script-list::-webkit-scrollbar-thumb:hover {
            background: #666;
        }
        .script-line {
            padding: 10px 12px;
            margin-bottom: 6px;
            border-radius: 6px;
            border-left: 3px solid transparent;
            transition: background 0.2s, border-color 0.2s;
            cursor: pointer;
            line-height: 1.5;
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .script-line:hover {
            background: #333;
        }
        .script-line.active {
            background: rgba(255, 100, 80, 0.15);
            border-left-color: #ff6450;
        }
        .script-line.active .time-badge {
            color: #ff6450;
        }
        .time-badge {
            font-size: 0.78rem;
            color: #888;
            margin-bottom: 0;
            flex: 0 0 auto;
            width: 72px;
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            letter-spacing: 0.2px;
        }
        .script-line .text {
            flex: 1 1 auto;
            min-width: 0;
        }
        .script-empty {
            margin: 0;
            padding: 16px;
            color: #888;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="video-wrap">
            <!-- video.php로 재생 시 Range 요청 지원 → 타임라인(seek) 가능 -->
            <video id="player" controls playsinline>
                <source src="video.php?f=<?= htmlspecialchars($videoFile) ?>" type="video/mp4">
                <!-- 브라우저가 비디오 재생을 지원하지 않습니다. -->
            </video>
            <p class="test-mode" id="testModeNotice" style="display:none; margin-top:8px; color:#888; font-size:0.9rem;">
                영상 로드에 실패했습니다. 아래 슬라이더로 재생 위치를 시뮬레이션할 수 있습니다.
            </p>
            <div class="test-slider-wrap" id="testSliderWrap" style="display:none; margin-top:8px;">
                <label style="display:block; margin-bottom:4px; color:#888;">재생 위치 시뮬레이션 (초)</label>
                <input type="range" id="timeSimulator" min="0" max="<?= (int) $videoDuration ?>" step="0.5" value="0" style="width:100%;">
                <span id="timeDisplay">0:00</span>
            </div>
            <a href="#" class="script-toggle" id="scriptToggle" title="스크립트 레이어 표시/숨김">스크립트 숨기기</a>
        </div>
        <div class="script-panel" id="scriptPanel">
            <h3>스크립트</h3>
            <div class="script-list" id="scriptList">   
                <?php if (!empty($scriptSegments)): ?>
                    <?php foreach ($scriptSegments as $i => $seg): ?>
                    <div class="script-line" data-start="<?= (float)$seg['start'] ?>" data-end="<?= (float)$seg['end'] ?>" data-index="<?= $i ?>">
                        <span class="time-badge"><?= format_display_time($seg['start']) ?></span>
                        <span class="text"><?= htmlspecialchars($seg['text']) ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="script-empty">해당 영상의 스크립트가 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
(function() {
    var player = document.getElementById('player');
    var scriptList = document.getElementById('scriptList');
    var lines = scriptList.querySelectorAll('.script-line');
    var lastActiveIndex = -1;
    var testMode = false;
    var simulatedTime = 0;

    function getCurrentTime() {
        return testMode ? simulatedTime : (player.currentTime || 0);
    }

    function getCurrentIndex() {
        var t = getCurrentTime();
        for (var i = 0; i < lines.length; i++) {
            var start = parseFloat(lines[i].getAttribute('data-start'));
            var end = parseFloat(lines[i].getAttribute('data-end'));
            if (t >= start && t < end) return i;
        }
        if (lines.length && t >= parseFloat(lines[lines.length - 1].getAttribute('data-end')))
            return lines.length - 1;
        return -1;
    }

    function setActive(index) {
        if (index === lastActiveIndex) return;

        console.log(index);
        for (var i = 0; i < lines.length; i++) {
            lines[i].classList.toggle('active', i === index);
        }
        lastActiveIndex = index;
        if (index >= 0) {
            var el = lines[index];
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
        }
    }

    function updateFromTime() {
        setActive(getCurrentIndex());
    }

    player.addEventListener('timeupdate', updateFromTime);
    player.addEventListener('seeked', updateFromTime);

    // 스크립트 라인 클릭 시 해당 시간으로 이동 후 재생 (seek 완료 후 play)
    for (var i = 0; i < lines.length; i++) {
        (function(line) {
            line.addEventListener('click', function() {
                var start = parseFloat(this.getAttribute('data-start'));
                if (testMode) {
                    simulatedTime = start;
                    var slider = document.getElementById('timeSimulator');
                    var display = document.getElementById('timeDisplay');
                    if (slider) { slider.value = start; }
                    if (display) { display.textContent = formatTimeDisplay(start); }
                } else {
                    player.currentTime = start;
                    function playAfterSeek() {
                        player.removeEventListener('seeked', playAfterSeek);
                        player.play();
                    }
                    player.addEventListener('seeked', playAfterSeek);
                    // seeked가 안 오는 경우(이미 해당 위치 등) 200ms 후 재생
                    setTimeout(function() {
                        if (player.paused) player.play();
                    }, 200);
                }
                updateFromTime();
            });
        })(lines[i]);
    }

    // 테스트 모드: 영상 로드 실패 시 슬라이더 표시
    var testSliderWrap = document.getElementById('testSliderWrap');
    var testModeNotice = document.getElementById('testModeNotice');
    var timeSimulator = document.getElementById('timeSimulator');
    var timeDisplay = document.getElementById('timeDisplay');

    player.addEventListener('error', function() {
        testMode = true;
        if (testModeNotice) testModeNotice.style.display = 'block';
        if (testSliderWrap) testSliderWrap.style.display = 'block';
    });

    function formatTimeDisplay(sec) {
        var s = Math.floor(sec);
        var h = Math.floor(s / 3600);
        var m = Math.floor((s % 3600) / 60);
        var ss = s % 60;
        if (h >= 1) return h + ':' + ('0' + m).slice(-2) + ':' + ('0' + ss).slice(-2);
        return m + ':' + ('0' + ss).slice(-2);
    }
    if (timeSimulator) {
        timeSimulator.addEventListener('input', function() {
            simulatedTime = parseFloat(this.value);
            if (timeDisplay) timeDisplay.textContent = formatTimeDisplay(simulatedTime);
            updateFromTime();
        });
    }

    // 스크립트 레이어 표시/숨김 토글
    var scriptToggle = document.getElementById('scriptToggle');
    var scriptPanel = document.getElementById('scriptPanel');
    if (scriptToggle && scriptPanel) {
        scriptToggle.addEventListener('click', function(e) {
            e.preventDefault();
            var hidden = scriptPanel.classList.toggle('hidden');
            scriptToggle.textContent = hidden ? '스크립트 보기' : '스크립트 숨기기';
        });
    }
})();
    </script>
</body>
</html>
