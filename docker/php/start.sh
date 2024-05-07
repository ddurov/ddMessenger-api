chown -R root:www-data /var/www/
chmod g+w -R /var/www/
php src/cli.php orm:schema-tool:update --force
if [ $? -ne 0 ]; then
    echo "Database are not configured"
    exit 61
fi
php-fpm