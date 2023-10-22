php app/cli.php orm:schema-tool:update --force
if [ $? -ne 0 ]; then
    echo "Database are not configured"
    exit 61
fi
PHP_CLI_SERVER_WORKERS=64 php -S 0.0.0.0:"$WEB_PORT" web/cli.php