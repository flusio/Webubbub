#!/usr/bin/env sh

php ./webubbub --request /subscriptions/validate
php ./webubbub --request /subscriptions/verify
php ./webubbub --request /subscriptions/expire
php ./webubbub --request /contents/fetch
php ./webubbub --request /contents/deliver

[ $(( $RANDOM % 60 )) == 0 ] && php ./webubbub --request /system/clean
