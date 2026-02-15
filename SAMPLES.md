# 샘플 영상 & 스크립트

## 스크립트 (로드 순서)

1. **videoplayback.txt** – 시간줄(M:SS) + 텍스트줄 형식 (권장)
2. **videoplayback.srt** – 표준 SRT 형식
3. **sample_script.php** – videoplayback_script.json 또는 내장 fallback

스크립트 구간을 바꾸려면 `videoplayback.txt`를 수정하거나, JSON 형식이면 `videoplayback_script.json`을 수정하면 됩니다.

**script_data.php** – 스크립트를 JSON으로 반환하는 API.

---

## 영상

- **videoplayback.mp4** – `video.php?f=videoplayback.mp4`로 재생 (Range/seek 지원).
- 영상이 없거나 로드 실패 시 **재생 위치 시뮬레이션 슬라이더**로 동작 확인 가능.
