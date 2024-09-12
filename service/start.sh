php cli.php orm:schema-tool:update --complete -f
if [ $? -ne 0 ]; then
    echo "Database are not configured"
    exit 61
fi
exec php-fpm -F -R