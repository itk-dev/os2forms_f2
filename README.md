# OS2Forms: F2 integration

1. Install the module

   ``` shell
   drush pm:install os2forms_f2
   ```

2. Go to `/admin/os2forms_f2/settings` and define settings.
3. Add a "F2" handler to a webform.

## Development

We use [DDEV Drupal Contrib](https://github.com/ddev/ddev-drupal-contrib) for development[^1].

[^1]: The DDEV environment has been created by running

      ```shell
      ddev config --project-type=drupal10 --docroot=web
      ddev dotenv set .ddev/.env.web --drupal-core '^10.5.10'
      ```

Start the show by running

```shell
task ddev:start
```

Run `task` to see other useful development tasks.
