# shellcheck disable=SC2046
export $(cat .env)
php App/cli.php orm:schema-tool:update --force
php -S 0.0.0.0:8001 -t web/