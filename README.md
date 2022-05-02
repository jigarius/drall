# Drall

Drall is a tool that helps run [drush](https://www.drush.org/) commands
on multi-site Drupal installations.

> One command to *drush* them all.
> — [Jigarius](https://jigarius.com/about)

A big thanks and shout-out to [Symetris](https://symetris.ca/) for sponsoring
the initial development of Drall.

## Installation

Drall is listed on [Packagist.org](https://packagist.org/packages/jigarius/drall).
Thus, it can easily be installed using `composer` as follows:

    composer require jigarius/drall

## Commands

To see a list of commands offered by Drall, run `drall list`. If you feel lost,
run `drall help` or continue reading this documentation.

### exec:drush

There are a number of ways to run `drush` commands on multiple sites.

#### With @@uri

In this method, the `--uri` option is sent to `drush`.

    drall exec:drush --uri=@@uri core:status

Or simplify omit the `--uri=@@uri` and it will be added automatically.

    drall exec:drush core:status

##### Example

```
$ drall core:status
drush --uri=default core:status
drush --uri=donnie core:status
drush --uri=leo core:status
drush --uri=mikey core:status
drush --uri=ralph core:status
```

Here, the `--uri` is populated with names of the subdirectories under `sites`
in which the various sites live.

#### With @@site

In this method, a site alias is sent to `drush`.

    drall exec:drush @@site.local core:status

##### Example

```shell
$ drall exec:drush @@site.local core:status
drush @tmnt.local core:status
drush @donnie.local core:status
drush @leo.local core:status
drush @mikey.local core:status
drush @ralph.local core:status
```

Here, `@@site` is replaced with site names detected from various site alias
definitions.

### exec:shell

If you want to run non-drush commands on your sites, or run multiple commands
on each site in your multi-site Drupal installation, this command comes to the
rescue.

This command simply takes one or more shell commands, replaces placeholders
like `@@uri` or `@@site` and executes them.

**Important:** You can only use any one of the possible placeholders, e.g. if
you use `@@uri` and you cannot mix it with `@@site`.

#### Example: Usage

```shell
$ drall exec:shell cat web/sites/@@uri/settings.local.php
cat web/sites/default/settings.local.php
cat web/sites/donnie/settings.local.php
cat web/sites/leo/settings.local.php
cat web/sites/mikey/settings.local.php
cat web/sites/ralph/settings.local.php
```

#### Example: Multiple commands

    drall exec:shell "drush @@site.dev updb -y && drush @@site.dev cim -y && drush @@site.dev cr"

### site:directories

Get a list of all available site directory names in the Drupal installation.
All `sites/*` directories containing a `settings.php` file are treated as
individual sites.

#### Example: Usage

```
$ drall site:directories
default
donnie.com
leo.com
mikey.com
ralph.com
```

The output can then be iterated with scripts.

#### Example: Iterating

```shell
for site in $(drall site:directories)
do
  echo "Current site: $site";
done;
```

### site:aliases

Get a list of site aliases.

#### Example: Usage

```shell
$ drall site:aliases
@tmnt.local
@donnie.local
@leo.local
@mikey.local
@ralph.local
```

The output can then be iterated with scripts.

#### Example: Iterating

```shell
for site in $(drall site:aliases)
do
  echo "Current site: $site";
done;
```

## Global options

This section covers some options that are supported by all `drall` commands.

### --root

Specify a Drupal project root or document root directory.

    drall --root=/path/to/drupal site:directories

### --drall-group

Specify the target site group. See the section *site groups* for more
information on site groups.

    drall exec:drush --drall-group=GROUP core:status --field=site

If `--drall-group` is not set, then the Drall uses the environment variable
`DRALL_GROUP`, if it is set.

### --drall-verbose

Whether Drall should display verbose output.

### --drall-debug

Whether Drall should display debugging output.

## Site groups

Drall allows you to group your sites so that you can run commands on these
groups using the `--drall-group` option.

### Drall groups with site aliases

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

### Drall groups with sites.*.php

If your project doesn't use site aliases, you can still group your sites using
one or more `sites.GROUP.php` files like this:

```php
# File: sites.bluish.php
$sites['donnie.drall.local'] = 'donnie';
$sites['leo.drall.local'] = 'leo';
```

This puts the sites `donnie` and `leo` in a group named `bluish`.

## Parallel execution (Experimental)

Say you have 100 sites in a Drupal installation. By default, Drall runs
commands on these sites one after the other. To speed up the execution, you
can ask Drall to execute multiple commands in parallel. You can specify the
number of workers with the `--drall-workers=n` option, where `n` is the
number of processes you want to run in parallel.

Please keep in mind that running a high number of workers can create problems!
The maximum number of workers allowed is set to `10` at the moment.

### Example: Parallel execution

The command below launches 3 instances of Drall to run `core:rebuild` command.

  drall exec:drush core:rebuild --drall-workers=3

When a worker runs out of work, it terminates automatically.

## Development

Here's how you can set up a local dev environment.

- Clone the `https://github.com/jigarius/drall` repository.
  - Use a branch as per your needs.
- Run `docker compose up -d`.
- Run `docker compose start`.
- Run `make ssh` to launch a shell in the Drupal container.
- Run `make provision`.
- Run `drall --version` to test the setup.
- Run `make lint` to run linter.
- Run `make test` to run tests.

You should now be able to `make ssh` and then run `drall`. A multi-site Drupal
installation should be present at `/opt/drupal`. Oh! And Drall should be
present at `/opt/drall`.

### Hosts

To access the dev sites in your browser, add the following line to your hosts
file. It is usually located at `/etc/hosts`. This is completely optional, so
do this only if you need it.

  127.0.0.1 tmnt.drall.local donnie.drall.local leo.drall.local mikey.drall.local ralph.drall.local

The sites should then be available at:
- [tmnt.drall.local](http://tmnt.drall.local/)
- [donnie.drall.local](http://donnie.drall.local/)
- [leo.drall.local](http://leo.drall.local/)
- [mikey.drall.local](http://mikey.drall.local/)
- [ralph.drall.local](http://ralph.drall.local/)

## Acknowledgements

- Thanks [Symetris](https://symetris.ca/) for funding the initial development.
- Thanks [Jigar Mehta (Jigarius)](https://jigarius.com/about) (that's me) for
  spending evenings and weekends to make this tool possible.
