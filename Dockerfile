FROM wordpress:latest

# Set the working directory
WORKDIR /var/www/html

# Copy the application code
COPY app/public /var/www/html

# Adjust permissions for the web server
RUN chown -R www-data:www-data /var/www/html

# Use the official entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
