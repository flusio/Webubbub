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

**Webubbub only supports SQLite database and PHP from 8.0 to 8.2.**
You’ll also need the PHP extension [`pcntl`](https://www.php.net/manual/book.pcntl.php).

That being said, here’s the instructions to install Webubbub. It’s expected you
already have a server with a Web server and <abbr>PHP</abbr> installed. The
instructions are documented for Nginx but feel free [to open a
ticket](https://github.com/flusio/Webubbub/issues/new) to add the instructions
to configure another kind of server.

---

First, download [the latest version](https://github.com/flusio/Webubbub/releases/latest)
of the code with Git:

```console
$ git clone --recurse-submodules https://github.com/flusio/Webubbub.git
$ cd Webubbub
$ git checkout <latest version tag>
```

It’s best to use Git since it’ll allow easy update later (mainly a `git pull`).
It also allows you to keep track of the changes you made on your server
(**which you never should do!**)

Then, you must create a `.env` file:

```console
$ cp env.sample .env
$ vim .env # or edit with nano or whatever editor you prefer
```

The environment file is commented so it should not be too complicated to setup
correctly.

Then, you have to initialize the database:

```console
$ php cli migrations setup
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

The last step is to configure the asynchronous jobs.

If you have root access on your server, you can configure a Systemd service.
First, create a `webubbub.service` file under `/etc/systemd/system/`:

```systemd
[Unit]
Description=A job worker for Webubbub

[Service]
ExecStart=php /var/www/Webubbub/cli jobs watch
User=www-data
Group=www-data

Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

Then, enable and start the service with:

```console
$ sudo systemctl enable webubbub.service
$ sudo systemctl start webubbub.service
```

This will start a Jobs Worker in background wich will verify subscriptions and deliver new contents.

If you prefer, you can configure a Cron task instead.
For instance, with `crontab -u www-data -e`:

```cron
* * * * * php /var/www/Webubbub/cli jobs watch --stop-after=5 >>/var/log/webubbub-jobs.txt 2>&1
```
