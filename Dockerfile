# Utiliser une image de base officielle PHP avec Apache
FROM php:8.1-apache

# Installer les dépendances système nécessaires pour le fonctionnement de certaines extensions PHP et Composer
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    git \
    unzip \
    libzip-dev \
    && apt-get clean \
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

# Assurer que le fichier de configuration de Composer est présent avant de lancer install
RUN if [ ! -f /var/www/composer.json ]; then echo "Composer.json not found." && exit 1; fi

# Exécuter Composer en tant qu'utilisateur www-data pour éviter les avertissements et installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Changer les permissions du répertoire de l'application pour le serveur web
RUN chown -R www-data:www-data /var/www

# Exposer le port sur lequel Apache est configuré pour écouter (par défaut 80)
EXPOSE 80

# Commande pour démarrer Apache lors du lancement du conteneur
CMD ["apache2-foreground"]
