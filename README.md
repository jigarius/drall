# Drall

Drall is a tool to that helps run [drush](https://www.drush.org/) commands on multi-site Drupal installations.

## Installation

Drall is listed on [Packagist.org](https://packagist.org/packages/jigarius/drall).
Thus, it can easily be installed using `composer` as follows:

```
composer require jigarius/drall
```

## Commands

To see a list of commands offered by drall, run `drall list`.

### site:directories

Get a list of all available site directory names in the Drupal installation.
All `sites/*` directories containing a `settings.php` file are treated as
individual sites.

```
$ drall site:directories
default
donnie.com
leo.com
mikey.com
ralph.com
```

The output can then be iterated with scripts.

### site:aliases

Get a list of site aliases.

```
$ drall site:aliases
@tmnt.local
@donnie.local
@leo.local
@mikey.local
@ralph.local
```

The output can then be iterated with scripts.

### exec

There are a number of ways to run `drush` commands on multiple sites.

#### With --uri

In this method, the `--uri` option is sent to `drush`.

```
drush st --fields=site
```

For example, the above command results in:

```
drush --uri=default st --fields=site
drush --uri=donnie st --fields=site
drush --uri=leo st --fields=site
drush --uri=mikey st --fields=site
drush --uri=ralph st --fields=site
```

Here, the `--uri` is populated with names of the subdirectories under `sites`
in which the various sites live.

#### With @alias

In this method, a site alias is sent to `drush`.

```
drall exec @@site.local st --fields=site
```

For example, the above command results in:

```
drush @tmnt.local st --fields=site
drush @donnie.local st --fields=site
drush @leo.local st --fields=site
drush @mikey.local st --fields=site
drush @ralph.local st --fields=site
```

Here, `@@site` is replaced with site names detected from various site alias
definitions.

## Site groups

Drall allows you to group your sites so that you can run commands on such
groups with ease.

```
drall exec --drall-group=GROUP core:rebuild
```

Here's how you can create site groups.

### With site aliases

In a site alias definition file, you can assign site aliases to one or more
groups like this:

```yaml
# File: tnmt.site.yml
local:
  root: /opt/drupal/web
  uri: http://tmnt.com/
  # ...
  drall:
    groups:
      - cartoon
      - action
```

This puts the alias `@tnmt.local` in the `cartoon` and `action` groups.

### With sites.php

If your project doesn't use site aliases, you can still group your sites using
one or more `sites.GROUP.php` files like this:

```php
# File: sites.bluish.php
$sites['donnie.drall.local'] = 'donnie';
$sites['leo.drall.local'] = 'leo';
```

This puts the sites `donnie` and `leo` in a group named `bluish`.

## Development

Here's how you can set up a local dev environment.

- Clone the `https://github.com/jigarius/drall` repository.
  - Use a branch as per your needs.
- Run `docker compose up -d`.
- Run `docker compose start`.
- Run `make ssh` to launch a shell in the Drupal container.
- Run `make provision`.
- Run `drall --version` to test the setup.

You should now be able to `make ssh` and then run `drall`. A multi-site
Drupal installation should be present at `/opt/drupal`.

### Hosts

To access the dev sites in your browser, add the following line to your hosts
file. It is usually located at `/etc/hosts`.

```
127.0.0.1 tmnt.drall.local donnie.drall.local leo.drall.local mikey.drall.local ralph.drall.local
```

The sites should then be available at:
- http://tmnt.drall.local/
- http://donnie.drall.local/
- http://leo.drall.local/
- http://mikey.drall.local/
- http://ralph.drall.local/
