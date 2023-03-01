```
git clone https://github.com/kwarnkham/clinic.git
git config credential.helper store
cp .env.example ./.env
nano .env
composer install --optimize-autoloader --no-dev
php artisan key:generate
php artisan config:cache
php artisan optimize
php artisan route:cache
php artisan view:cache

php artisan migrate --seed
sudo chown -R www-data.www-data /var/www/clinic/storage /var/www/clinic/bootstrap/cache
sudo chmod -R 755 /var/www/clinic/storage /var/www/clinic/bootstrap/cache
```

# Update no docker

```
php artisan down
git pull
composer install --optimize-autoloader --no-dev
php artisan migrate --force

php artisan config:cache && php artisan optimize && php artisan route:cache && php artisan view:cache

php artisan up
systemctl restart nginx
```
