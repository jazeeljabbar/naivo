#!bin/bash

# wait for mysql to start its services
until mysql -h ${MYSQL_HOST} -P ${MYSQL_PORT} -u ${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} -e 'select 1'; do
    >&2 echo "MySQL is unavailable - sleeping"
    sleep 5
done
>&2 echo "Mysql is up - executing command"

dir="${WORKDIR}"

# give write permission to the volume
chmod -R 777 /var/www/html/

# Set up wordpress admin
wp core install --path="$dir" --title="${WP_TITLE}" --url="${WP_HOST}:${WP_PORT}" --admin_user="${WP_ADMIN_USERNAME}" --admin_password="${WP_ADMIN_PASSWORD}" --admin_email="${WP_ADMIN_EMAIL}" --allow-root

# Set up wordpress siteurl
wp option update siteurl "${WP_HOST}:${WP_PORT}/wordpress" --allow-root

# Set up wordpress home url
wp option update home "${WP_HOST}:${WP_PORT}/wordpress" --allow-root

# Set up wordpress blog name
wp option update blogname "${WP_BLOGNAME}" --allow-root

# Install woocommerce plugin
wp plugin install woocommerce --path="$dir" --allow-root --activate

sleep infinity