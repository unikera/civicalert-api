# Utiliser une image de base officielle PHP avec Apache
FROM php:8.1-apache

# Afficher la version PHP pour vérification
RUN php -v

# Installer les dépendances système, y compris le client MySQL, git et les outils nécessaires pour le fonctionnement de certaines extensions PHP et Composer
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    git \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP requises
RUN docker-php-ext-install pdo_mysql zip

# Activer le mod_rewrite pour Apache
RUN a2enmod rewrite

# Copier les fichiers de configuration Apache pour Slim
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Installer Composer pour la gestion des dépendances PHP
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers de l'application dans l'image
COPY . /var/www

# Exécuter Composer en tant qu'utilisateur non root pour éviter les avertissements et installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Changer les permissions du répertoire de l'application pour le serveur web
RUN chown -R www-data:www-data /var/www

# Exposer le port sur lequel Apache est configuré pour écouter (par défaut 80)
EXPOSE 80

# Commande pour démarrer Apache lors du lancement du conteneur
CMD ["apache2-foreground"]
