php app/cli.php orm:schema-tool:update --force
if [ $? -ne 0 ]; then
    echo "Database are not configured"
    exit 61
fi
php -S 0.0.0.0:"$WEB_PORT" web/index.php