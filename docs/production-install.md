# How to install Webubbub (production)

Webubbub is written in <abbr>PHP</abbr> and has very few dependencies. The consequence
is it’s quite easy to install on your own server. Yet, it’s important to note
that, if you install Webubbub, you’ll act as an intermediate between
subscribers and publishers: **you must be reliable.** That implies:

- your server is never down too long;
- you address basic security considerations (HTTPS, server up to date, backups,
  etc.);
- you don’t spy on your users, nor you sell their data.

Also, **you must be able to set up a virtual host.** Shared hosters often don’t
allow that, and you should not install Webubbub if you can’t. This is for
security reasons, to don’t expose all the files on the Web, especially the
`data` directory. If you can afford it, you can rent a small VPS for cheap.

**Webubbub only supports SQLite database and PHP from 7.2 to 7.4 for now.**

That being said, here’s the instructions to install Webubbub. It’s expected you
already have a server with a Web server and <abbr>PHP</abbr> installed. The
instructions are documented for Nginx but feel free [to open a
ticket](https://github.com/flusio/Webubbub/issues/new) to give us instructions
to configure another kind of server.

---

First, download [the latest version](https://github.com/flusio/Webubbub/releases/latest)
of the code with Git:

```console
$ git clone https://github.com/flusio/Webubbub.git
$ cd Webubbub
$ git checkout <latest version tag>
```

It’s best to use Git since it’ll allow easy update later (mainly a `git pull`).
It also allows you to keep track of the changes you made on your server
(**which you never should do!**)

Then, you have to initialize the database:

```console
$ php ./webubbub --request /system/init
```

You should find a database under `./data/db.sqlite`. Now, give correct
ownership to the Webubbub files:

```console
$ sudo chown -R www-data:www-data .
```

Then, configure your virtual host to serve PHP files from the `./public`
directory. You can find [an example file for Nginx in the documentation](./webubbub.nginx.conf)
to put under `/etc/nginx/sites-available/`. Unless you know what you do,
**configure the server in HTTP at this step** (i.e. one `server` block
listening on port 80, no `ssl_*` directives).

If it isn’t, install `certbot` to generate a HTTPS certificate ([official
instructions](https://certbot.eff.org/)). Then, if you use Nginx, just run the
command:

```console
$ sudo certbot --nginx
```

It’ll ask few questions. When it asks for updating your configuration, just
accept and it should update your configuration with correct SSL directives.

You can enable the site with:

```console
$ sudo ln -s /etc/nginx/sites-available/webubbub.conf /etc/nginx/sites-enabled/webubbub.conf
$ sudo systemctl reload nginx
```

**You should be able to access your Webubbub instance URL now!**

The last step is to configure the asynchronous jobs. You can configure a Cron
task every minute with `crontab -u www-data -e`:

```cron
* * * * * sh /var/www/Webubbub/bin/jobs.sh
```

This will verify subscriptions and deliver new content. You can configure a
smaller frequence by creating a Systemd timer instead of a Cron task. First,
create a `webubbub.service` file under `/etc/systemd/system/` ([example](./systemd/webubbub.service)).
Then, a `webubbub.timer` under the same folder ([example](./systemd/webubbub.timer)).
**It is important both files have the same basename!** You can enable and start
the timer with:

```console
$ sudo systemctl enable webubbub.timer
$ sudo systemctl start webubbub.timer
```

This will execute the jobs every 10 seconds (or any other frequency you might
have set). A better jobs system will be designed later.
