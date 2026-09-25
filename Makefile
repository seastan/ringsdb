build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

sh:
	docker compose exec -it -u www-data symfony sh

phpunit:
	docker compose exec -it -u www-data symfony php bin/simple-phpunit
