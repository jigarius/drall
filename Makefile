.PHONY: ssh
ssh:
	docker compose exec main bash


.PHONY: provision
provision: provision/drall provision/drupal


.PHONY: provision/drupal
provision/drupal:
	mkdir -p /opt/drupal
	cp /opt/drall/.docker/main/composer.json /opt/drupal/ || echo "Skipping drupal/composer.json"
	rm -f /opt/drupal/composer.lock
	composer --working-dir=/opt/drupal install --no-progress

	cp -r /opt/drall/.docker/main/drush /opt/drupal/ || echo "Skipping drush directory."
	cp -r /opt/drall/.docker/main/sites /opt/drupal/web/ || echo "Skipping sites directory."

	mkdir -p /opt/drupal/web/sites/default
	mkdir -p /opt/drupal/web/sites/donnie
	mkdir -p /opt/drupal/web/sites/leo
	mkdir -p /opt/drupal/web/sites/mikey
	mkdir -p /opt/drupal/web/sites/ralph

	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/default/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/donnie/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/leo/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/mikey/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/ralph/settings.php

	@echo 'Drupal databases can be provisioned with: make provision/drupal/database'


.PHONY: provision/drupal/database
provision/drupal/database:
	rm -f web/sites/*/settings.php

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
	composer install --working-dir=/opt/drall --no-progress
	drall --version || exit 1


.PHONY: coverage-report/text
coverage-report/text:
	cat /opt/drall/.coverage/text


.PHONY: coverage-report/html
coverage-report/html:
	open .coverage/html/dashboard.html


.PHONY: lint
lint:
	composer --working-dir=/opt/drall run lint


.PHONY: test
test:
	DRALL_ENVIRONMENT=test XDEBUG_MODE=coverage composer --working-dir=/opt/drall run test
