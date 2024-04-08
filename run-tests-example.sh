#!/bin/sh

(
export TEST_ANAF_REQUESTS=true
export TEST_ANAF_CLIENT_CIF=YOUR_CIF_HERE
# Uncomment the following line to test only the empty list
# export ANAF_EMPTY_LIST_ONLY=true
./vendor/bin/phpunit tests --display-skipped
)

