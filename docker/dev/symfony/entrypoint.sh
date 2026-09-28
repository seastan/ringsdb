#!/usr/bin/env sh

composer install
php app/console server:run 0.0.0.0