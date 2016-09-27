if [ ! -d "/tmp/uploads" ]; then
  mkdir /tmp/uploads
  chown wwwrun:wwwrun /tmp/uploads
fi

cd /var/www/sample
composer install
