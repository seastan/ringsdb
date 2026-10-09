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
	$(EXEC_SYMFONY) php bin/console doctrine:fixtures:load --append --no-debug

test-fixtures:
	$(EXEC_SYMFONY) php bin/console doctrine:database:drop --env=test --force --if-exists
	$(EXEC_SYMFONY) php bin/console doctrine:database:create --env=test
	$(EXEC_MYSQL)_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_bootstrap.sql
	$(EXEC_MYSQL)_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_reset_auto_increment.sql
	# stored function used by the card statistics; created as root (binary logging requires SUPER)
	$(EXEC_MYSQL)_test mysql -u root -ppasswd ringsdb_test < function-source-code.sql
	$(EXEC_SYMFONY) php bin/console doctrine:migrations:migrate -n --env=test
	$(EXEC_SYMFONY) php bin/console doctrine:fixtures:load --append --env=test --no-debug

# Xdebug is off: with it (develop mode), PHP segfaults in the middle of the suite
PHPUNIT := $(EXEC) -it -u www-data -e XDEBUG_MODE=off symfony php vendor/bin/phpunit

phpunit: test-fixtures
	$(PHPUNIT)

phpunit-update-snapshots: test-fixtures
	$(EXEC) -it -u www-data -e XDEBUG_MODE=off -e UPDATE_SNAPSHOTS=1 symfony php vendor/bin/phpunit

# Code coverage report in var/cache/coverage/index.html (uses Xdebug)
coverage: test-fixtures
	$(EXEC) -it -u www-data -e XDEBUG_MODE=coverage symfony php vendor/bin/phpunit --coverage-html var/cache/coverage --coverage-text=php://stdout --colors=never
	@echo "Code coverage report: \033[36mfile://${PWD}/var/cache/coverage/index.html\033[0m"

# cache:warmup: the service types are read from the dumped container (see phpstan.neon.dist)
phpstan:
	$(EXEC_SYMFONY) php bin/console cache:warmup --env=test
	$(EXEC_SYMFONY) php vendor/bin/phpstan --memory-limit=-1

# All the deprecations, including the indirect ones (triggered in vendor/, even when caused by
# src/, e.g. validation annotations on an entity), which phpunit.dist.xml ignores
deprecations: test-fixtures
	sed 's/ignoreIndirectDeprecations="true"/ignoreIndirectDeprecations="false"/' phpunit.dist.xml > .phpunit-deprecations.xml
	$(PHPUNIT) -c .phpunit-deprecations.xml --no-logging; status=$$?; rm -f .phpunit-deprecations.xml; exit $$status

lint-twig:
	$(EXEC_SYMFONY) php bin/console lint:twig templates

lint-container:
	$(EXEC_SYMFONY) php bin/console lint:container

clear-cache:
	$(EXEC_SYMFONY) php bin/console cache:clear --env=test
	$(EXEC_SYMFONY) php bin/console cache:clear --env=dev

cs:
	$(EXEC_SYMFONY) php vendor/bin/php-cs-fixer fix

rector:
	$(EXEC_SYMFONY) php vendor/bin/rector --dry-run

rector-fix:
	$(EXEC_SYMFONY) php vendor/bin/rector

all: install lint-container lint-twig rector cs phpstan phpunit

reset:
	rm -rf var/ vendor/ public/bundles public/css public/js