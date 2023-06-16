# Webubbub changelog

## xxxx-yy-zz - 1.0.0-alpha-2

### Migration notes

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

## 2019-12-28 - 1.0.0-alpha

Initial release
