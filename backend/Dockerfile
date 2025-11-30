FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (SQLite)
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Set working directory
WORKDIR /app

# Copy backend files
COPY . /app/

# Create uploads directory with proper permissions
RUN mkdir -p /app/uploads/lesson && \
    chmod -R 755 /app/uploads

# Create database directory with proper permissions
RUN mkdir -p /app/data && \
    chmod -R 755 /app/data

# Copy database initialization script and schema
COPY database/init_sqlite.php /app/database/
COPY database/database_init_sqlite.sql /app/database/

# Copy startup script
COPY start.sh /app/start.sh
RUN chmod +x /app/start.sh

# Expose port (Render will set PORT environment variable)
EXPOSE $PORT

# Use startup script
CMD ["/app/start.sh"]

