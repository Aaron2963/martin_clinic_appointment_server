cd /D "%~dp0/../"
del /f .\data\db_table.json
docker-compose -f docker-compose.yml stop && docker-compose -f docker-compose.yml down --rmi local -v
docker-compose -f docker-compose.yml up -d
timeout 10
docker-compose -f docker-compose.yml exec web composer install --working-dir=/var/www/site
docker-compose -f docker-compose.yml exec web php /var/www/dev/deploy-database.php
