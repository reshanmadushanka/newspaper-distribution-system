#!/bin/bash

# Deployment script for Newspaper Distribution Application
# This script can be run manually on the VPS or called by CI/CD pipeline

set -e  # Exit on error

echo "🚀 Starting deployment..."

# Configuration
APP_DIR="/var/www/newspaper-distribution-system"
BRANCH="${1:-main}"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Navigate to app directory
cd "$APP_DIR" || { echo -e "${RED}❌ Cannot access $APP_DIR${NC}"; exit 1; }

echo -e "${YELLOW}📥 Pulling latest code from $BRANCH...${NC}"
git pull origin "$BRANCH"

echo -e "${YELLOW}📦 Installing PHP dependencies...${NC}"
composer install --prefer-dist --no-dev --no-ansi --no-interaction --optimize-autoloader

echo -e "${YELLOW}📦 Installing Node dependencies...${NC}"
npm ci

echo -e "${YELLOW}🔨 Building frontend assets...${NC}"
npm run build

echo -e "${YELLOW}🗄️ Running database migrations...${NC}"
php artisan migrate --force

echo -e "${YELLOW}⚡ Caching configurations...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${YELLOW}🔄 Restarting queue workers...${NC}"
sudo supervisorctl restart newspaper-queue:* 2>/dev/null || echo -e "${YELLOW}⚠️ Queue workers not found or already stopped${NC}"

echo -e "${YELLOW}🔐 Setting permissions...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo -e "${YELLOW}🧹 Clearing caches...${NC}"
php artisan cache:clear
php artisan optimize:clear

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${GREEN}🎉 Your application is now live!${NC}"
