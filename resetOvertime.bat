@echo off
SETLOCAL ENABLEDELAYEDEXPANSION
REM ɾ������ʼ�����ݿ�
set a=%cd%
cd /d D:\WNMPSVR\mysql\bin
set MY=.\mysql.exe
set DB=overtime
set PASSOPTION=
set IP=127.0.0.1
set PORT=3306
set USER=root

set /p DBIP=���������ݿ�IP������Ϊ127.0.0.1�������س���
set /p DBPORT=���������ݿ�˿ڣ�����Ϊ3306�������س���
set /p DBUSER=���������ݿ��û�������Ϊroot�������س���
set /p DBPSWD=���������ݿ��û����루����Ϊ�գ������س���
REM echo "1"
if NOT "%DBIP%" == "" set IP=%DBIP%
REM echo "2"
if NOT "%DBPORT%" == "" set PORT=%DBPORT%
REM echo "3"
if NOT "%DBUSER%" == "" set USER=%DBUSER%
REM echo "4"
if NOT "%DBPSWD%" == "" set PASSOPTION="-p%DBPSWD%"

REM %MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION% -e "show databases;"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "DROP DATABASE %DB%;"
echo "1"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET character_set_client=utf8;"
echo "2"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET character_set_connection=utf8;"
echo "3"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET character_set_database=utf8;"
echo "4"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET character_set_results=utf8;"
echo "5"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET character_set_server=utf8;"
echo "6"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET collation_connection=utf8;"
echo "7"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET collation_database=utf8;"
echo "8"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET collation_server=utf8;"
echo "9"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "SET NAMES 'utf8';"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -e "CREATE DATABASE IF NOT EXISTS %DB% DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;"
REM echo "%DB%"
%MY% -h %IP% -P %PORT% -u%USER% %PASSOPTION%  -D%DB% -e "show tables;"

cd %a%