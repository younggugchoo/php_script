# PHP 구동 환경 구성 (Windows)

이 프로젝트는 **PHP만** 있으면 됩니다. Apache/Nginx 등 별도 웹서버 없이 PHP 내장 서버로 실행할 수 있습니다.

---

## 방법 1: 공식 PHP 수동 설치 (권장)

### 1단계: PHP 다운로드

1. 브라우저에서 **https://windows.php.net/download/** 접속
2. **PHP 8.3** 또는 **8.4** 중 하나 선택
3. **VS16 x64 Non Thread Safe** ZIP 다운로드  
   (예: `php-8.3.x-nts-Win32-vs16-x64.zip`)
4. ZIP 압축 해제 후 폴더 이름을 `php`로 바꾸고, 원하는 위치에 둡니다.  
   예: `C:\php`

### 2단계: PATH 등록

1. **Windows 키** → "환경 변수" 검색 → **시스템 환경 변수 편집** 실행
2. **환경 변수** 버튼 클릭
3. **시스템 변수**에서 **Path** 선택 → **편집** → **새로 만들기**
4. PHP 폴더 경로 입력 (예: `C:\php`) → **확인**으로 모두 닫기
5. **새 터미널(또는 CMD/PowerShell)을 연 뒤** 아래로 버전 확인:

```powershell
php -v
```

`PHP 8.3.x ...` 처럼 나오면 성공입니다.

### 3단계: 프로젝트 실행

이 폴더에서 아래 중 하나로 실행합니다.

- **더블클릭:** `run.bat`
- **또는 터미널에서:**

```powershell
cd d:\DEV_STUDY\php_script
php -S localhost:8080
```

브라우저에서 **http://localhost:8080** 접속 후 `index.php`를 열면 됩니다.

---

## 방법 2: Chocolatey로 설치 (명령어 한 번에)

Chocolatey가 이미 있다면:

```powershell
# 관리자 권한 PowerShell
choco install php -y
```

설치 후 **새 터미널**을 열고:

```powershell
cd d:\DEV_STUDY\php_script
php -S localhost:8080
```

Chocolatey가 없다면: https://chocolatey.org/install 에서 설치 후 위 명령 실행.

---

## 방법 3: XAMPP (PHP + Apache + MySQL 한 번에)

웹서버/DB까지 쓰고 싶다면:

1. https://www.apachefriends.org/ 에서 XAMPP 다운로드 및 설치
2. 설치 후 **XAMPP Control Panel**에서 **Apache** 시작
3. 프로젝트 폴더를 XAMPP의 `htdocs` 안으로 복사  
   예: `C:\xampp\htdocs\php_script`
4. 브라우저에서 **http://localhost/php_script/** 접속

이 경우 `run.bat` 대신 Apache가 PHP를 실행합니다.

---

## 문제 해결

| 증상 | 조치 |
|------|------|
| `php -v` 시 "인식할 수 없음" | PATH에 PHP 폴더가 추가되었는지 확인. 터미널을 **다시 연 뒤** 다시 시도. |
| `php -S localhost:8080` 실패 | 8080 포트를 다른 프로그램이 쓰는지 확인. `php -S localhost:8888` 처럼 다른 포트로 시도. |
| 스크립트 한글 깨짐 | `index.php` 상단에 `header('Content-Type: text/html; charset=utf-8');` 있는지 확인. (현재 HTML에서 charset=UTF-8 지정됨) |

---

## 요약

- **필요한 것:** PHP만 (별도 웹서버 불필요)
- **실행:** 이 폴더에서 `run.bat` 실행 후 브라우저에서 **http://localhost:8080** 접속
