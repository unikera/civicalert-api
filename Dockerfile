# Utiliser une image de base officielle PHP avec Apache
FROM php:8.1-apache

RUN php -v

# Mettre à jour la liste des paquets et installer le client MySQL
RUN apt-get update && apt-get install -y default-mysql-client && \
    docker-php-ext-install pdo_mysql

# Activer le mod_rewrite pour Apache
RUN a2enmod rewrite

# Copier les fichiers de configuration Apache pour Slim
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Installer Composer en mode non root pour éviter les avertissements
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=0

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers de l'application dans l'image
COPY . /var/www

# Installer les dépendances de l'application via Composer
RUN composer install --no-dev --optimize-autoloader

# Changer les permissions du répertoire de l'application pour le serveur web
RUN chown -R www-data:www-data /var/www/html

# Exposer le port sur lequel Apache est configuré pour écouter (par défaut 80)
EXPOSE 80

# Commande pour démarrer Apache lors du lancement du conteneur
CMD ["apache2-foreground"]
