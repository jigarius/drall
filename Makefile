.PHONY: ssh
ssh:
	docker compose exec main bash


.PHONY: provision
provision: provision/drall provision/drupal


.PHONY: provision/drupal
provision/drupal:
	mkdir -p /opt/drupal/web/sites/donnie
	mkdir -p /opt/drupal/web/sites/leo
	mkdir -p /opt/drupal/web/sites/mikey
	mkdir -p /opt/drupal/web/sites/ralph

	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/default/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/donnie/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/leo/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/mikey/settings.php
	cp /opt/drupal/web/sites/default/default.settings.php /opt/drupal/web/sites/ralph/settings.php

	rm -Rf /opt/drupal/composer.lock
	composer --working-dir=/opt/drupal install --no-progress

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

	# The GitHub Action shivammathur/setup-php@v2 gives higher priority to
  # the executables present in /opt/drall/vendor/bin. Thus, we remove
  # Drush from this directory to force /opt/drupal/vendor/bin/drush.
	rm -f /opt/drall/vendor/bin/drush


.PHONY: coverage-text
coverage-report/text:
	cat .coverage/text


.PHONY: coverage-html
coverage-report/html:
	open .coverage/html/dashboard.html


.PHONY: lint
lint:
	composer --working-dir=/opt/drall run lint


.PHONY: test
test:
	DRALL_ENVIRONMENT=test composer --working-dir=/opt/drall run test


.PHONY: info
info:
	@cd /opt/drupal

	which php
	@php --version

	which composer
	@composer --version

	which drush
	@drush --version

	which drall
	@drall --version
