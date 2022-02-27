# Drall

A tool to run drush commands on multi-site Drupal installations.

## Commands

To see a list of commands offered by drall, run `drall list`.

### site:directories

Get a list of all available site directory names in the Drupal installation.
All `sites/*` directories containing a `settings.php` file are treated as
individual sites.

```
$ drall site:directories
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

Run a `drush` command on multiple sites.

```
drall exec st --fields=site
```
