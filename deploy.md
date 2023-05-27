1. clone the repo

```
git clone https://github.com/kwarnkham/clinic.git
cd clinic
```

2. fill up env

```
cp .env.example ./.env
nano .env
```

# Update

```
php artisan down
git pull
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize
php artisan up
systemctl restart nginx

* * * * * cd /etc/nginx/html/clinic-api && php artisan schedule:run >> /dev/null 2>&1
```

# Move server

```
cd /etc/nginx/html/
git clone https://github.com/kwarnkham/clinic.git
cd clinic
cp .env.example ./.env
nano .env
composer install --optimize-autoloader --no-dev
php artisan migrate
php artisan storage:link
cd storage/app/public/

move the files

create vh file for nginx

cd /etc/nginx/conf.d/

for ubuntu
sudo chown -R www-data:www-data /etc/nginx/html/clinic/storage /etc/nginx/html/clinic/bootstrap/cache
sudo chmod -R 755 /etc/nginx/html/clinic/storage /etc/nginx/html/clinic/bootstrap/cache

for centos
chown -R nginx:nginx /etc/nginx/html/clinic-api/storage /etc/nginx/html/clinic-api/bootstrap/cache
chmod -R 0777 /etc/nginx/html/clinic-api/storage
chmod -R 0775 /etc/nginx/html/clinic-api/bootstrap/cache


backup db

scp clinic.dump root@coffee.book-mm.com:/root/
mysql clinic < /root/clinic.dump

php artisan optimize && php artisan view:cache

systemctl restart nginx

```
