@echo off
REM EPES Daily Backup — Windows Task Scheduler entry point
REM Schedule this via: schtasks /create /tn "EPES_Daily_Backup" /tr "C:\xampp\htdocs\epes\backup_scheduled.cmd" /sc daily /st 02:00 /ru SYSTEM /f

C:\xampp\php\php.exe C:\xampp\htdocs\epes\backup_run.php >> C:\xampp\htdocs\epes\backups\backup_log.txt 2>&1
