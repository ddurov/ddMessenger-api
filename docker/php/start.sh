rm /tmp/ddLogs/*.log &> /dev/null
php app/cli.php orm:schema-tool:update --force
if [ $? -ne 0 ]; then
    echo "Database are not configured"
    exit 61
fi
usermod --non-unique --uid 1000 www-data
/usr/sbin/apache2ctl -D FOREGROUND