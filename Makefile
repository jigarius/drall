.PHONY: ssh
ssh:
	docker compose exec main bash


.PHONY: provision
provision:
	apt-get update
	apt-get install -y mariadb-client git unzip
	@make provision/drupal
	@make provision/drall


.PHONY: provision/drupal
provision/drupal:
	cd /opt/drupal
	composer install

	drush site:install -y minimal --db-url="mysql://drupal:drupal@database:3306/tmnt" --uri=default --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=TMNT
	chown -R www-data:www-data web/sites/default

	drush site:install -y minimal --sites-subdir=donnie --db-url="mysql://drupal:drupal@database:3306/donnie" --uri=donnie --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Donatello
	chown -R www-data:www-data web/sites/donnie

	drush site:install -y minimal --sites-subdir=leo --db-url="mysql://drupal:drupal@database:3306/leo" --uri=leo --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Leonardo
	chown -R www-data:www-data web/sites/leo

	drush site:install -y minimal --sites-subdir=mikey --db-url="mysql://drupal:drupal@database:3306/mikey" --uri=mikey --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Michaelangelo
	chown -R www-data:www-data web/sites/mikey

	drush site:install -y minimal --sites-subdir=ralph --db-url="mysql://drupal:drupal@database:3306/ralph" --uri=ralph --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Raphael
	chown -R www-data:www-data web/sites/ralph


.PHONY: provision/drall
provision/drall:
	cd /opt/drall
	composer install
	export PATH="$PATH:/opt/drall/bin"
