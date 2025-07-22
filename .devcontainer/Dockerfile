FROM mcr.microsoft.com/devcontainers/php:8.3

# Install additional PHP extensions if needed
RUN apt-get update && apt-get install -y \
  git \
  curl \
  libpng-dev \
  libonig-dev \
  libxml2-dev \
  zip \
  unzip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install additional PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

USER vscode
