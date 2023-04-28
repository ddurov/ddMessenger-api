start-containers:
	docker-compose -f docker/docker-compose.yml -p messager --env-file .env up --build -d

stop-containers:
	docker-compose -f docker/docker-compose.yml -p messager --env-file .env down