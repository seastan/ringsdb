build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

sh:
	docker compose exec -it -u www-data symfony sh

sql:
	docker compose exec -it mysql mysql -u root -ppasswd

assets:
	docker compose exec -it -u www-data symfony php bin/console app:assets

install:
	docker compose exec -it -u www-data symfony composer install

fixtures:
	docker compose exec -it -u www-data symfony php bin/console doctrine:database:drop --force
	docker compose exec -it -u www-data symfony php bin/console doctrine:database:create
	docker compose exec -T mysql mysql -u symfony -ppasswd ringsdb < ringsdb_bootstrap.sql
	docker compose exec -T mysql mysql -u symfony -ppasswd ringsdb < ringsdb_reset_auto_increment.sql
	# stored function used by the card statistics; created as root (binary logging requires SUPER)
	docker compose exec -T mysql mysql -u root -ppasswd ringsdb < function-source-code.sql
	docker compose exec -it -u www-data symfony php bin/console doctrine:migrations:migrate -n
	docker compose exec -it -u www-data symfony php bin/console doctrine:fixtures:load --append

test-fixtures:
	docker compose exec -it -u www-data symfony php bin/console doctrine:database:drop --env=test --force
	docker compose exec -it -u www-data symfony php bin/console doctrine:database:create --env=test
	docker compose exec -T mysql_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_bootstrap.sql
	docker compose exec -T mysql_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_reset_auto_increment.sql
	# stored function used by the card statistics; created as root (binary logging requires SUPER)
	docker compose exec -T mysql_test mysql -u root -ppasswd ringsdb_test < function-source-code.sql
	docker compose exec -it -u www-data symfony php bin/console doctrine:migrations:migrate -n --env=test
	docker compose exec -it -u www-data symfony php bin/console doctrine:fixtures:load --append --env=test

phpunit: test-fixtures
	docker compose exec -it -u www-data symfony php vendor/bin/simple-phpunit

# Code coverage report in var/cache/coverage/index.html (uses Xdebug)
coverage: test-fixtures
	docker compose exec -it -u www-data symfony php vendor/bin/simple-phpunit --coverage-html var/cache/coverage --coverage-text=php://stdout --colors=never
	@echo "Code coverage report: \033[36mfile://${PWD}/var/cache/coverage/index.html\033[0m"

# cache:warmup: the service types are read from the dumped container (see phpstan.neon.dist)
phpstan:
	docker compose exec -it -u www-data symfony php bin/console cache:warmup --env=test
	docker compose exec -it -u www-data symfony php vendor/bin/phpstan --memory-limit=-1

deprecations:
	docker compose exec -it -u www-data -e SYMFONY_DEPRECATIONS_HELPER=verbose=max[total]=999999 symfony php vendor/bin/simple-phpunit

lint-twig:
	docker compose exec -it -u www-data symfony php bin/console lint:twig templates

clear-cache:
	docker compose exec -it -u www-data symfony php bin/console cache:clear --env=test
	docker compose exec -it -u www-data symfony php bin/console cache:clear --env=dev

all: install lint-twig phpstan phpunit
