RingsDB
=======

# Very quick guide on how to install a local copy

This guide assumes you know how to use the command-line and that your machine has php and mysql installed.

- install composer: https://getcomposer.org/download/
- clone the repo somewhere
- cd to it
- set the database configuration (`DATABASE_*`) in `.env.local` (the defaults are in `.env`, the
  values of the Docker stack in `.env.dev`)
- run `composer install`
- run `php bin/console doctrine:database:create`
- run `php bin/console doctrine:schema:create`
- import data into mysql
- run `php bin/console server:run`

To update all JavaScript and CSS assets, run:

- `php bin/console app:assets`


To run a command:

- `php bin/console app:beorn:scenario`
