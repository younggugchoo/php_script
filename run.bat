@echo off
chcp 65001 >nul
title PHP - php_script
echo.
echo [PHP 내장 서버] 프로젝트 폴더에서 실행 중...
echo.
echo   브라우저에서 열기: http://localhost:8080
echo   index.php: http://localhost:8080/index.php
echo.
echo   종료: 이 창에서 Ctrl+C
echo.

php -v 2>nul
if errorlevel 1 (
    echo [오류] PHP를 찾을 수 없습니다.
    echo.
    echo SETUP.md 를 참고해 PHP를 설치하고 PATH에 추가한 뒤
    echo 다시 run.bat 을 실행하세요.
    echo.
    pause
    exit /b 1
)

php -S localhost:8080
pause
