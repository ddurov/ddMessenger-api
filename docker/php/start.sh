sleep 5
php app/cli.php orm:schema-tool:update --force
php -S 0.0.0.0:$WEB_PORT -t web/