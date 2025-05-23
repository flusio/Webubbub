# Webubbub changelog

## 2025-05-13 - 1.0.1

### Security

- Reject contents from invalid origins ([5075169](https://github.com/flusio/Webubbub/commit/5075169))

### Improvements

- Delete Content if fetching fails ([f81262c](https://github.com/flusio/Webubbub/commit/f81262c))

### Technical

- Update the dependencies ([c51a14c](https://github.com/flusio/Webubbub/commit/c51a14c))

### Documentation

- Remove websub.flus.io from the open hubs ([4d3ba22](https://github.com/flusio/Webubbub/commit/4d3ba22))

### Developers

- Refactor checking allowed topic origins ([09a94df](https://github.com/flusio/Webubbub/commit/09a94df))

## 2025-04-15 - 1.0.0

### Migration notes

Webubbub now requires PHP 8.2+ and uses Composer to manage its dependencies in production.
You must make sure your server matches the new requirements and that you have Composer installed on it.
Then, follow the standard procedure to update Webubbub.

### Technical

- Add support for PHP 8.4 ([231682e](https://github.com/flusio/Webubbub/commit/231682e))
- Configure Webubbub with Composer ([b5eecf8](https://github.com/flusio/Webubbub/commit/b5eecf8))
- Update the Composer dependencies ([b40fc10](https://github.com/flusio/Webubbub/commit/b40fc10))
- Fix small issues in the Makefile ([7a0bb8f](https://github.com/flusio/Webubbub/commit/7a0bb8f))

### Documentation

- Improve paragraph about contributing ([3e1d3db](https://github.com/flusio/Webubbub/commit/3e1d3db))
- Document the seed argument in the installation guide ([9c3d322](https://github.com/flusio/Webubbub/commit/9c3d322))

### Developers

- Add a make release command ([46a5c3f](https://github.com/flusio/Webubbub/commit/46a5c3f))
- Update to PHPStan and Rector ^2.0 ([c68d3dd](https://github.com/flusio/Webubbub/commit/c68d3dd))
- Allow to change the port of Nginx in development ([7b65596](https://github.com/flusio/Webubbub/commit/7b65596))
- Improve the pull request template ([94a00b3](https://github.com/flusio/Webubbub/commit/94a00b3))
- Configure Rector ([b6c4165](https://github.com/flusio/Webubbub/commit/b6c4165))
- Allow to pass a LINTER argument to make lint command ([b3fc254](https://github.com/flusio/Webubbub/commit/b3fc254))
- Configure PHPCS with a file ([6c01590](https://github.com/flusio/Webubbub/commit/6c01590))
- Rename phpstan.neon to .phpstan.neon ([4d9eede](https://github.com/flusio/Webubbub/commit/4d9eede))
- Upgrade to PHPUnit 11 ([52251a8](https://github.com/flusio/Webubbub/commit/52251a8))
- Update the Docker Compose configuration ([12696f2](https://github.com/flusio/Webubbub/commit/12696f2))

## 2023-06-16 - 1.0.0-alpha-2

### Migration notes

Webubbub now required PHP 8.0+.
The PHP extension `pcntl` is now required.

You’ll have to create a `.env` file, based on the [`env.sample`](/env.sample) one.
It’s no longer necessary to set env variables in the Nginx file.

Right after pulling the changes, execute this command:

```console
$ echo 'Migration201912260001AddPendingSecretAndPendingLeaseSecondsToSubscription' > data/migrations_version.txt
```

The script `bin/jobs.sh` has been removed in favor of a better system.
You should change your cron job or your systemd timer with the documented service (see [the production doc](/docs/production-install.md)).

The format of the commands have changed.
To learn more, read [the documentation](/docs/cli.md), or execute:

```console
$ php cli help
```

### Security

### New

- Add an option to restrict the allowed topics ([6007039](https://github.com/flusio/Webubbub/commit/6007039))

### Improvements

- Update the home page ([868db08](https://github.com/flusio/Webubbub/commit/868db08))
- Provide a command to clean the system ([a489cea](https://github.com/flusio/Webubbub/commit/a489cea))

### Bug fixes

- Fix handling of lease\_seconds in Requests ([d81f294](https://github.com/flusio/Webubbub/commit/d81f294))
- Don’t urldecode properties of new Subscription ([02103dc](https://github.com/flusio/Webubbub/commit/02103dc))
- Catch bad secret error on renew ([d670be7](https://github.com/flusio/Webubbub/commit/d670be7))

### Technical

- Drop support of PHP 7 ([cdd35b5](https://github.com/flusio/Webubbub/commit/cdd35b5))
- Install the dev dependencies with Composer ([dd1951e](https://github.com/flusio/Webubbub/commit/dd1951e))
- Replace Travis by Github Actions ([b2d748f](https://github.com/flusio/Webubbub/commit/b2d748f))
- Upgrade Minz to its latest version ([96f0979](https://github.com/flusio/Webubbub/commit/96f0979))
- Configure PHPStan ([c83ce85](https://github.com/flusio/Webubbub/commit/c83ce85))
- Rename the webubbub command into cli ([aa75fa4](https://github.com/flusio/Webubbub/commit/aa75fa4))
- Improve the Makefile commands ([9742819](https://github.com/flusio/Webubbub/commit/9742819))
- Extract a script to exec php command in Docker ([2b906f7](https://github.com/flusio/Webubbub/commit/2b906f7))
- Add project name to docker-compose commands ([16333ab](https://github.com/flusio/Webubbub/commit/16333ab))
- Configure xdebug with PHPUnit ([60d08a9](https://github.com/flusio/Webubbub/commit/60d08a9))
- Remove useless PHP extension on the CI ([37e7b22](https://github.com/flusio/Webubbub/commit/37e7b22))

### Misc

- Add AGPL license ([36258eb](https://github.com/flusio/Webubbub/commit/36258eb))
- Add a README and basic documentation ([08ac1e1](https://github.com/flusio/Webubbub/commit/08ac1e1))
- Add a PR template ([1575ac6](https://github.com/flusio/Webubbub/commit/1575ac6))

## 2019-12-28 - 1.0.0-alpha

Initial release
