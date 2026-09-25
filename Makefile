build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

sh:
	docker compose exec -it -u www-data symfony sh

fixtures:
	docker compose exec -it -u www-data symfony php app/console doctrine:database:drop --force
	docker compose exec -it -u www-data symfony php app/console doctrine:database:create
	docker compose exec -T mysql mysql -u symfony -ppasswd ringsdb < ringsdb_bootstrap.sql
	docker compose exec -T mysql mysql -u symfony -ppasswd ringsdb < ringsdb_reset_auto_increment.sql
	docker compose exec -it -u www-data symfony php app/console doctrine:fixtures:load --append

test-fixtures:
	docker compose exec -it -u www-data symfony php app/console doctrine:database:drop --env=test --force
	docker compose exec -it -u www-data symfony php app/console doctrine:database:create --env=test
	docker compose exec -T mysql_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_bootstrap.sql
	docker compose exec -T mysql_test mysql -u symfony -ppasswd ringsdb_test < ringsdb_reset_auto_increment.sql
	docker compose exec -it -u www-data symfony php app/console doctrine:fixtures:load --append --env=test

phpunit: test-fixtures
	docker compose exec -it -u www-data symfony php bin/simple-phpunit
