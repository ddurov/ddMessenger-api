start-containers:
	docker compose -f docker/docker-compose.yml -p messenger --env-file .env up --build -d

stop-containers:
	docker compose -f docker/docker-compose.yml -p messenger --env-file .env down