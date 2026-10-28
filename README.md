# OS2Forms: F2 integration

This module add a F2 webform handler that can archive content from a OS2Forms Attachment webform element in
[F2](https://www.cbrain.com/software-pages/the-f2-platform).

The handler can achive on a case and on a matter. Documents in F2 can only be attached to matters, so when archiving on
a case, the handler first creates a matter before attaching a document to it.

## Installation

1. Install the module

   ``` shell
   drush pm:install os2forms_f2
   ```

2. Go to `/admin/os2forms_f2/settings` and define settings for connecting to the F2 API

> [!TIP]
> Use the `os2forms-f2:api-client` Drush command to check that the API connection works as expected. Run
>
> ```shell
> drush os2forms-f2:api-client --help
> ```
>
> for some help and examples on how to use the command.

## Usage

When the F2 module has been installed and configured, an F2 handler can be added to a webform.

See the [user manual for administrators](docs/user-manual/da/administrator.md) and [for form
builders](docs/user-manual/da/form-builder.md) for more details on how to use the handler (both are in Danish).

### Examples

Install the F2 form examples module (`drush pm:install os2forms_f2_examples`) to load an example webform with F2
handlers.

## Development

We use [DDEV Drupal Contrib](https://addons.ddev.com/addons/ddev/ddev-drupal-contrib) for development.

<details>
<summary>Details on the DDEV setup</summary>

The DDEV environment has been created by following the template on <https://github.com/ddev/ddev-drupal-contrib#install>:

```shell
ddev config --project-type=drupal11 --docroot=Drupal
ddev add-on get ddev/ddev-drupal-contrib
ddev add-on get julienloizelet/ddev-playwright
ddev start
ddev poser --no-interaction
ddev symlink-project
# Detect expected Drupal and PHP versions.
ddev config --update
```

During development, we often have the Drupal modules cloned into `web/sites/default/modules` in a Drupal project. Using
`Drupal` as docroot affectively hides the DDEV site from the Drupal project during extension discovery (`Drupal` is
ignored by the [`RecursiveExtensionFilterCallback`
class](https://git.drupalcode.org/project/drupal/-/blob/11.4.11/core/lib/Drupal/Core/Extension/Discovery/RecursiveExtensionFilterCallback.php?ref_type=tags&plain=1#L46-74)).

We have a dependency on [OS2Forms](https://github.com/OS2Forms/os2forms) which requires [Drupal
Lenient](https://github.com/mglaman/composer-drupal-lenient) to install some outdated modules, and therefore we use [a
DDEV hook](https://docs.ddev.com/en/stable/users/configuration/hooks/) to make sure that Drupal Lenient is installed
before we run any actual `composer install` commands:

``` yaml
# .ddev/config.yaml
hooks:
  post-start:
    # Install Drupal Lenient (https://github.com/mglaman/composer-drupal-lenient)
    - exec: COMPOSER=composer.lenient.json composer init --no-interaction
    - exec: COMPOSER=composer.lenient.json composer config --no-plugins allow-plugins.mglaman/composer-drupal-lenient true
    - exec: COMPOSER=composer.lenient.json composer require mglaman/composer-drupal-lenient:^1.0
    - exec: rm -f composer.lenient.*
```

</details>

Start the show by running

```shell
task ddev:start
```

Run `task` to see other useful development tasks.

## Translations

Run

``` shell
task translations:extract
```

to extract translations.

[Poedit](https://poedit.com/) (or similar tools) can be used to edit the generated `po` files.

> [!IMPORTANT]
> After editing a `po` file, you should run `task translations:extract` to format the translation file as the
> translation extractor does it.

Run

``` shell
task translations:git:restore
```

to discard any non-essential changes to translation files (e.g. timestamps being updated without any actual translation
changes).

## Testing

``` shell
task drupal:os2forms:install
```

## User manual

The Danish user manual for the F2 module sits in [`docs/user-manual/da/`](docs/user-manual/da/).

We use [Playwright](https://playwright.dev/) to generate images for the user manual – and perform some testing along the
way – and we use [ddev-playwright](https://addons.ddev.com/addons/julienloizelet/ddev-playwright) to run Playwright in
the DDEV environment.

Before generating the images a
little setup must be added in `tests/Playwright/.env.local`:

``` dotenv
# Settings used to connect to a real F2 API
F2_API_URI=
F2_API_USERNAME=
F2_API_SECRET=
F2_API_F2_USERNAME=

# An exiting case identifier
F2_CASE_IDENTIFIER=

# An exiting matter identifier
F2_MATTER_IDENTIFIER='903'
```

With these in place, we can now generate images:

``` shell
task drupal:os2forms:install --yes
task playwright:install --yes
task user-manual:images:generate
```

> [!TIP]
> If something fails, run
>
> ``` shell
> open tests/Playwright/playwright-report/index.html
> ```
>
> to see an error report.

When editing the user manual and fooling around with Playwright to generate useful images, you can run

``` shell
task user-manual:images:generate
```

to quickly check that everything works as expected, but before committing anything you must run

``` shell
task drupal:os2forms:install --yes
task user-manual:images:generate
```

reset Drupal before generating the images.

> [!TIP]
> See [Run a Playwright command](https://addons.ddev.com/addons/julienloizelet/ddev-playwright#run-a-playwright-command)
> for other cool tricks supported by DDEV and the Playwright add-on.
