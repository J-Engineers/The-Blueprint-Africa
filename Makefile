setup:
	@make build
	@make up 
	@make composer-update
build:
	docker-compose build --no-cache --force-rm
stop:
	docker-compose stop
up:
	docker-compose up -d  --remove-orphans
composer-update:
	docker exec phpcontainer bash -c "composer update"
data:
	docker exec phpcontainer bash -c "php artisan migrate"
	docker exec phpcontainer bash -c "php artisan db:seed"
container:
	docker exec -it phpcontainer bash
ip:
	docker inspect -f '{{.Name}} - {{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' $(docker ps -aq)
containers:
	docker ps -a