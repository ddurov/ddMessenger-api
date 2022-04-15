#!/bin/bash
openssl s_client -verify_quiet -connect $1:443 | openssl x509 -pubkey -noout | openssl pkey -pubin -outform der | openssl dgst -sha256 -binary | openssl enc -base64
