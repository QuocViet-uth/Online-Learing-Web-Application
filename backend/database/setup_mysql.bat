@echo off
echo Creating MySQL database...
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS online_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo Importing schema...
"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root online_learning < "C:\laragon\www\learningweb\backend\database\database_init.sql"
echo Done! Database created successfully.
pause
