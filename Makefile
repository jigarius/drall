.PHONY: ssh
ssh:
	docker compose exec main bash


.PHONY: provision
provision: provision/env provision/drall provision/no-drupal provision/empty-drupal provision/drupal


.PHONY: provision/env
provision/env:
	cp /opt/drall/bin/drall-launcher /usr/local/bin/drall
	cp /opt/drall/bin/drush-launcher /usr/local/bin/drush


.PHONY: provision/drupal
provision/drupal:
	mkdir -p /opt/drupal

	cp /opt/drall/.docker/main/drupal/composer.json /opt/drupal/ || echo "Skipping: drupal/composer.json"
	rm -f /opt/drupal/composer.lock
	composer --working-dir=/opt/drupal install --no-progress
	cp -r /opt/drall/.docker/main/drupal/drush /opt/drupal/ || echo "Skipping: drupal/drush"
	cp -r /opt/drall/.docker/main/drupal/web/sites /opt/drupal/web/ || echo "Skipping: drupal/web/sites"

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

	@echo ''
	@echo 'Drupal databases can be provisioned with: make provision/drupal/database'


.PHONY: provision/no-drupal
provision/no-drupal:
	mkdir -p /opt/no-drupal
	cp /opt/drall/.docker/main/no-drupal/composer.json /opt/no-drupal/ || echo "Skipping: no-drupal/composer.json"
	rm -f /opt/no-drupal/composer.lock
	composer --working-dir=/opt/no-drupal install --no-progress


.PHONY: provision/empty-drupal
provision/empty-drupal:
	mkdir -p /opt/empty-drupal
	cp /opt/drall/.docker/main/empty-drupal/composer.json /opt/empty-drupal/ || echo "Skipping: empty-drupal/composer.json"
	rm -f /opt/empty-drupal/composer.lock
	composer --working-dir=/opt/empty-drupal install --no-progress
	cp /opt/drall/.docker/main/empty-drupal/web/sites/sites.php /opt/empty-drupal/web/sites/sites.php


.PHONY: provision/drupal/database
provision/drupal/database:
	rm -f web/sites/*/settings.php

	./vendor/bin/drush site:install -y minimal --db-url="mysql://drupal:drupal@database:3306/tmnt" --uri=default --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=TMNT
	chown -R www-data:www-data web/sites/default

	./vendor/bin/drush site:install -y minimal --sites-subdir=donnie --db-url="mysql://drupal:drupal@database:3306/donnie" --uri=donnie --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Donatello
	chown -R www-data:www-data web/sites/donnie

	./vendor/bin/drush site:install -y minimal --sites-subdir=leo --db-url="mysql://drupal:drupal@database:3306/leo" --uri=leo --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Leonardo
	chown -R www-data:www-data web/sites/leo

	./vendor/bin/drush site:install -y minimal --sites-subdir=mikey --db-url="mysql://drupal:drupal@database:3306/mikey" --uri=mikey --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Michaelangelo
	chown -R www-data:www-data web/sites/mikey

	./vendor/bin/drush site:install -y minimal --sites-subdir=ralph --db-url="mysql://drupal:drupal@database:3306/ralph" --uri=ralph --account-name=tmnt-root --account-mail=tmnt@localhost --account-pass=cowabunga --site-name=Raphael
	chown -R www-data:www-data web/sites/ralph


.PHONY: provision/drall
provision/drall:
	composer install --working-dir=/opt/drall --no-progress


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
	XDEBUG_MODE=coverage composer --working-dir=/opt/drall run test


.PHONY: info
info:
	@echo "Path: $(PATH)"
	@echo "PWD: $(PWD)"
	@echo "Drupal path: $(DRUPAL_PATH)"
	@composer --version
	which drall
