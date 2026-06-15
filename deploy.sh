#!/bin/bash
# Run from /var/www/ringsdb
set -e

cd "$(dirname "$0")"

echo "Clearing prod cache..."
php app/console cache:clear --env=prod

echo "Dumping assets..."
php app/console assetic:dump --env=prod

echo "Fixing permissions..."
sudo setfacl -R -m u:rings:rwx app/
sudo setfacl -R -m u:www-data:rwx app/

echo "Done."
