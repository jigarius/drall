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

## Placeholders

Drall's functioning depends on its _Placeholders_. Here's how Drall works
under the hood:

1. Receive a command, say, `drall exec COMMAND`.
2. Ensure there is a `@@placeholder` in `COMMAND`.
3. Run `COMMAND` after replacing `@@placeholder` with site-specific values.
4. Display the result.

Drall supports the following placeholders:

### @@dir

This placeholder is replaced with the name of the site's directory under
Drupal's `sites` directory. These are the values of the `$sites` array
usually defined in `sites.php`.

```php
# @@dir is replaced with "ralph" and "leo".
$sites['raphael.com'] = 'ralph';
$sites['leonardo.com'] = 'leo';
```

**Note:** In older versions of Drall, this was called `@@uri`.

### @@key

This placeholder is replaced with keys of the `$sites` array.

```php
# @@key is replaced with "raphael.com", "raphael.local" and "leonardo.com".
$sites['raphael.com'] = 'ralph';
$sites['raphael.local'] = 'ralph';
$sites['leonardo.com'] = 'leo';
```

### @@ukey

This placeholder is replaced with unique keys of the `$sites` array. If a site
has multiple keys, the last one is used as its unique key.

```php
# @@key is replaced with "raphael.local" and "leonardo.local".
$sites['raphael.com'] = 'ralph';
$sites['raphael.local'] = 'ralph';
$sites['leonardo.com'] = 'leo';
$sites['leonardo.local'] = 'leo';
```

### @@site

This placeholder is replaced with the first part of the site's alias.

```shell
# @@site is replaced with "@ralph" and "@leo".
@ralph.local
@leo.local
```

**Note:** This placeholder only works for sites with Drush aliases.

## Commands

To see a list of commands offered by Drall, run `drall list`. If you feel lost,
run `drall help` or continue reading this documentation.

### exec

With `exec` you can execute drush as well as non-drush commands on multiple
sites in your Drupal installation.

In Drall 2.x there were 2 exec commands. These are now unified into a single
command just like version 1.x.
- `drall exec:drush ...` is now `drall exec drush ...`
- `drall exec:shell ...` is now `drall exec ...`

#### Drush with @@dir

In this method, the `--uri` option is sent to `drush`.

    drall exec drush --uri=@@dir core:status

If it is a Drush command and no valid `@@placeholder` are present, then
`--uri=@@dir` is automatically added after each occurrence of `drush`.

```shell
# Raw drush command (no placeholders)
drall exec drush core:status
# Command that is executed (placeholders injected)
drall exec drush --uri=@@dir core:status
```

##### Example

```shell
$ drall exec drush core:status
drush --uri=default core:status
drush --uri=donnie core:status
drush --uri=leo core:status
drush --uri=mikey core:status
drush --uri=ralph core:status
```

#### Drush with @@site

In this method, a site alias is sent to `drush`.

    drall exec drush @@site.local core:status

##### Example

```shell
$ drall exec drush @@site.local core:status
drush @tmnt.local core:status
drush @donnie.local core:status
drush @leo.local core:status
drush @mikey.local core:status
drush @ralph.local core:status
```

#### Non-drush commands

You can run non-Drush commands the same was as you run Drush commands. Just
make sure that the command has valid placeholders.

**Important:** You can only use any one of the possible placeholders, e.g. if
you use `@@dir` and you cannot mix it with `@@site`.

##### Example: Shell command

```shell
$ drall exec cat web/sites/@@uri/settings.local.php
cat web/sites/default/settings.local.php
cat web/sites/donnie/settings.local.php
cat web/sites/leo/settings.local.php
cat web/sites/mikey/settings.local.php
cat web/sites/ralph/settings.local.php
```

##### Example: Multiple commands

    drall exec "drush @@site.dev updb -y && drush @@site.dev cim -y && drush @@site.dev cr"

#### Options

For the `drall exec` command, all Drall parameters must be passed right
after the `drall exec`. Here's an example:

```shell
# This will work.
drall exec --drall-workers=4 drush cache:rebuild

# This won't work.
drall exec drush cache:rebuild --drall-workers=4
```

Besides the global options, the `exec` command supports the following options.

#### --drall-workers

Say you have 100 sites in a Drupal installation. By default, Drall runs
commands on these sites one after the other. To speed up the execution, you
can ask Drall to execute multiple commands in parallel. You can specify the
number of workers with the `--drall-workers=n` option, where `n` is the
number of processes you want to run in parallel.

Please keep in mind that the performance of the workers depends on your
resources available on the computer executing the command. If you have low
memory, and you run Drall with 4 workers, performance might suffer. Also,
some operations need to be executed sequentially to avoid competition and
conflict between the Drall workers.

##### Example: Parallel execution

The command below launches 3 instances of Drall to run `core:rebuild` command.

    drall exec drush core:rebuild --drall-workers=3

When a worker runs out of work, it terminates automatically.

#### --drall-no-progress

By default, Drall displays a progress bar that indicates how many sites have
been processed and how many are remaining. In verbose mode, this progress
indicator also displays the time elapsed.

However, the progress display that can mess with some terminals or scripts
which don't handle backspace characters. For these environments, the progress
bar can be disabled using the `--drall-no-progress` option.

##### Example: Hide progress bar

    drall exec --drall-no-progress drush core:rebuild

### site:directories

Get a list of all available site directory names in the Drupal installation.
All `sites/*` directories containing a `settings.php` file are treated as
individual sites.

#### Example: Usage

```shell
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

    drall exec --drall-group=GROUP core:status --field=site

If `--drall-group` is not set, then the Drall uses the environment variable
`DRALL_GROUP`, if it is set.

### --drall-filter

Filter placeholder values with an expression. This is helpful for running
commands on specific sites.

```shell
# Run only on the "leo" site.
drall exec --drall-filter=leo core:status
# Run only on "leo" and "ralph" sites.
drall exec --drall-filter="leo||ralph" core:status
```

For more on using filter expressions, refer to the documentation on
[consolidation/filter-via-dot-access-data](https://github.com/consolidation/filter-via-dot-access-data).

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

- Thanks, [Symetris](https://symetris.ca/) for funding the initial development.
- Thanks, [Jigarius](https://jigarius.com/about) (that's me) for spending
  evenings and weekends to make this tool possible.
