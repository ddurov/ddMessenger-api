build-image:
	docker build -t messager-api:latest .

start-container:
	docker run -v `pwd`/vendor:/root/vendor --env-file .env --name ddMessager -p 8001:8001 -d --restart unless-stopped messager-api:latest

stop-container:
	docker stop $$(docker ps -a -q -f ancestor=messager-api)

remove-exited-containers:
	docker rm -v $$(docker ps -a -q -f status=exited)

rebuild-with-remove:
	make stop-container
	make remove-exited-containers
	make build-image
	make start-container