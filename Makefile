start:
	docker compose -f docker-compose.yml -p messenger --env-file .env up --build -d

stop:
	docker compose -f docker-compose.yml -p messenger --env-file .env down