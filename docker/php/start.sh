while true; do
    x=0
    if [ $x -eq 10 ]; then
      echo "Database are not configured"
      exit 1
    fi
    php app/cli.php orm:schema-tool:update --force
    if [ $? -eq 0 ]; then
        break
    fi
    sleep 3
    ((x++))
done
php -S 0.0.0.0:"$WEB_PORT" -t web/