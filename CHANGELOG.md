# Webubbub changelog

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
