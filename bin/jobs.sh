#!/usr/bin/env sh

php cli --request /subscriptions/validate
php cli --request /subscriptions/verify
php cli --request /subscriptions/expire
php cli --request /contents/fetch
php cli --request /contents/deliver

[ $(( $RANDOM % 60 )) == 0 ] && php cli --request /system/clean
