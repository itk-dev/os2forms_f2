# OS2Forms: F2 integration

1. Install the module

   ``` shell
   drush pm:install os2forms_f2
   ```

2. Go to `/admin/os2forms_f2/settings` and define settings.
3. Add a "F2" handler to a webform.

## Development

We use [DDEV Drupal Contrib](https://github.com/ddev/ddev-drupal-contrib) for development.

```shell name=ddev-install
ddev config --project-type=drupal10 --docroot=web
```

```shell name=ddev-start
ddev add-on get ddev/ddev-drupal-contrib
ddev start
ddev poser
ddev symlink-project
# Detect expected Drupal and PHP versions.
ddev config --update
```

``` shell
ddev phpcbf
ddev phpcs
```
