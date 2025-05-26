@echo off
chcp 65001
set dateVar=%date:~0,4%%date:~5,2%%date:~8,2%
set timeVar=%time:~0,2%
if /i %timeVar% LSS 10 (set timeVar=0%time:~1,1%)
set timeVar=%timeVar%%time:~3,2%%time:~6,2%

set codePath=%~dp0

cd %codePath%
git pull
git add .
git commit -m "bat_commit: %date%%time%"
git push origin master
git push gitee master
::pause