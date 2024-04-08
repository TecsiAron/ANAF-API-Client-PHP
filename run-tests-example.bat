@echo off
set TEST_ANAF_REQUESTS=true
set TEST_ANAF_CLIENT_CIF=YOUR_CIF_HERE
REM Uncomment the following line to test only the empty list
REM set ANAF_EMPTY_LIST_ONLY=true
vendor\bin\phpunit tests --display-skipped