#!/usr/bin/env sh

if [ ! -f vendor/autoload.php ]; then
    composer install
fi

ln -sfn /var/card_images web/bundles/cards

# web/js/extra.js and app.js (to run again after a change of one of their files)
php app/console app:assets:js

php app/console server:run 0.0.0.0

# the server could not start (e.g. the kernel does not boot): keep the container up, so that
# "docker compose exec symfony ..." can be used to fix it
echo "server:run failed: the container stays up for debugging"
exec tail -f /dev/null
