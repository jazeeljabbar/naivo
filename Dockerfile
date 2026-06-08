FROM wordpress:latest

# Set the working directory
WORKDIR /var/www/html

# Copy the application code
COPY app/public /var/www/html

# Ensure uploads directory exists even if ignored in build
RUN mkdir -p /var/www/html/wp-content/uploads && \
    chown -R www-data:www-data /var/www/html

# Use the official entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
