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
