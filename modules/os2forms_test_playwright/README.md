# OS2Forms test Playwright

This module is used only for testing with [Playwright](https://playwright.dev/) and it provides a controller that can be
called to execute Drush commands.

> [!IMPORTANT]
> Do not enable this module in production!

The controller responds on `/os2forms-test-playwright/exec/drush` and to `POST` requests only. The content type *must*
be `application/json` and the body must be a JSON list of arguments and flags to Drush.

## Installation

``` shell
ddev drush pm:install os2forms_test_playwright
```

## Example

Call `drush status`:

``` shell
$ ddev exec curl http://localhost/os2forms-test-playwright/exec/drush --json '["status"]'
Drupal version   : 11.4.11
Site URI         : https://os2forms-f2.ddev.site:30001
DB driver        : mysql
DB hostname      : db
DB port          : 3306
DB username      : db
DB name          : db
Database         : Connected
Drupal bootstrap : Successful
Default theme    : gin
Admin theme      : gin
PHP binary       : /usr/bin/php8.4
PHP config       : /etc/php/8.4/cli/php.ini
PHP OS           : Linux
PHP version      : 8.4.27
Drush script     : /var/www/html/vendor/bin/drush.php
Drush version    : 13.8.0.0
Drush temp       : /tmp
Drush configs    : /var/www/html/vendor/drush/drush/drush.yml
Install profile  : standard
Drupal root      : /var/www/html/web
Site path        : sites/default
Files, Public    : sites/default/files
Files, Temp      : /tmp
Drupal config    : sites/default/files/sync
```
