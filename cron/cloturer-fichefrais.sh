0 0 1 * * mariadb -h db -e "source /var/www/GSB-Frais/cron/cloturer-fichefrais.sql;" >> /var/log/cron.log 2>&1
