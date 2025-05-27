@echo off
chcp 65001
rem set dateVar=%date:~0,4%%date:~5,2%%date:~8,2% rem 这个只能适用默认编码
set dateVar=%date:~3,4%%date:~8,2%%date:~11,2%
set timeVar=%time:~0,2%
if /i %timeVar% LSS 10 (set timeVar=0%time:~1,1%)
set timeVar=%timeVar%%time:~3,2%%time:~6,2%

cd /d %~dp0
git pull
git add .
git commit -m "bat_commit: %date%%time%"
git push origin master
git push gitee master
rem pause
timeout /t 5 /nobreak
