# Upgrading from 4.x to 5.x

Version 5.0.0 raises the minimum requirements to PHP 8.3 and Laravel 13.x.
Support for Laravel 12 and earlier is removed. Applications on an older Laravel
version should remain on a compatible 4.x package release until the application
and its other dependencies have been upgraded.

1. Upgrade the application to PHP 8.3 or later and Laravel 13, following the
   [Laravel upgrade guide](https://laravel.com/docs/13.x/upgrade).
2. Update the package requirement and resolve dependencies:

   ```shell
   composer require overtrue/laravel-subscribe:^5.0 --with-all-dependencies
   ```

3. Run the application's tests, particularly its subscription, event listener,
   polymorphic relationship, and custom model integrations.

This release does not change the package's subscription APIs, events,
configuration keys, or database schema. Existing published configuration and
migrations can stay in place; do not republish or rerun existing migrations just
for this upgrade.

For package contributors, the test suite now uses Orchestra Testbench 11 and
PHPUnit 12.5. CI runs the full suite on native PHP 8.3, 8.4, and 8.5, plus the
lowest dependency versions permitted by the constraints and Composer's security
advisory blocking on PHP 8.3. Security checks are not disabled for this job.
