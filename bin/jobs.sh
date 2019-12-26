#!/usr/bin/env sh

echo "Running subscriptions verifications..."
php ./webubbub --request /subscriptions/verify

echo "Running subscriptions expirations..."
php ./webubbub --request /subscriptions/expire

echo "Running contents fetching..."
php ./webubbub --request /contents/fetch

echo "Running contents delivering..."
php ./webubbub --request /contents/deliver

echo "All finished!"
