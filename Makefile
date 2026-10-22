EXEC := docker compose exec
EXEC_MYSQL := $(EXEC) -T mysql
EXEC_SYMFONY := $(EXEC) -it -u www-data -e http_proxy=${http_proxy} -e https_proxy=${https_proxy} -e no_proxy=${no_proxy} symfony

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

sh:
	$(EXEC_SYMFONY) sh

sql:
	$(EXEC) -it mysql mysql -u root -ppasswd

assets:
	$(EXEC_SYMFONY) php bin/console app:assets

install:
	$(EXEC_SYMFONY) composer install

fixtures:
	$(EXEC_SYMFONY) php bin/console doctrine:database:drop --force --if-exists
	$(EXEC_SYMFONY) php bin/console doctrine:database:create
	$(EXEC_MYSQL) mysql -u symfony -ppasswd ringsdb < ringsdb_bootstrap.sql
	$(EXEC_MYSQL) mysql -u symfony -ppasswd ringsdb < ringsdb_reset_auto_increment.sql
	# stored function used by the card statistics; created as root (binary logging requires SUPER)
	$(EXEC_MYSQL) mysql -u root -ppasswd ringsdb < function-source-code.sql
	$(EXEC_SYMFONY) php bin/console doctrine:migrations:migrate -n
	$(EXEC_SYMFONY) php bin/console doctrine:fixtures:load --append

test-fixtures:
	$(EXEC_SYMFONY) php bin/console doctrine:database:drop --env=test --force --if-exists
	$(EXEC_SYMFONY) php bin/console doctrine:database:create --env=test
	$(EXEC_MYSQL)_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_bootstrap.sql
	$(EXEC_MYSQL)_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_reset_auto_increment.sql
	# stored function used by the card statistics; created as root (binary logging requires SUPER)
	$(EXEC_MYSQL)_test mysql -u root -ppasswd ringsdb_test < function-source-code.sql
	$(EXEC_SYMFONY) php bin/console doctrine:migrations:migrate -n --env=test
	$(EXEC_SYMFONY) php bin/console doctrine:fixtures:load --append --env=test

phpunit: test-fixtures
	$(EXEC_SYMFONY) php vendor/bin/simple-phpunit

phpunit-update-snapshots: test-fixtures
	$(EXEC_SYMFONY) -it -u www-data -e UPDATE_SNAPSHOTS=1 symfony php vendor/bin/simple-phpunit

# Code coverage report in var/cache/coverage/index.html (uses Xdebug)
coverage: test-fixtures
	$(EXEC_SYMFONY) php vendor/bin/simple-phpunit --coverage-html var/cache/coverage --coverage-text=php://stdout --colors=never
	@echo "Code coverage report: \033[36mfile://${PWD}/var/cache/coverage/index.html\033[0m"

# cache:warmup: the service types are read from the dumped container (see phpstan.neon.dist)
phpstan:
	$(EXEC_SYMFONY) php bin/console cache:warmup --env=test
	$(EXEC_SYMFONY) php vendor/bin/phpstan --memory-limit=-1

deprecations:
	$(EXEC_SYMFONY) -it -u www-data -e SYMFONY_DEPRECATIONS_HELPER=verbose=max[total]=999999 symfony php vendor/bin/simple-phpunit

lint-twig:
	$(EXEC_SYMFONY) php bin/console lint:twig templates

clear-cache:
	$(EXEC_SYMFONY) php bin/console cache:clear --env=test
	$(EXEC_SYMFONY) php bin/console cache:clear --env=dev

cs:
	$(EXEC_SYMFONY) php vendor/bin/php-cs-fixer fix

rector:
	$(EXEC_SYMFONY) php vendor/bin/rector

all: install lint-twig rector cs phpstan phpunit
