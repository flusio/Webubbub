#!/bin/sh

while getopts m:c:t: option
do
    case "${option}" in
        m) MODE=${OPTARG};;
        c) CALLBACK=${OPTARG};;
        t) TOPIC=${OPTARG};;
    esac
done

curl -d "hub.mode=${MODE}&hub.callback=${CALLBACK}&hub.topic=${TOPIC}" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -X POST \
     http://127.0.0.1:8000
