# Learn the command line interface (CLI)

Webubbub provides a handy tool to test and manipulate what’s going on in the
application. If you have any doubt, you can open the [src/Application.php](../src/Application.php)
file and search for the `cli` routes.

A command is called with:

```console
$ php webubbub --request /path
```

`/path` must be replaced by a registered route path (the second argument of the
`addRoute` method). Additional parameters can be passed with the `-p` flag. For
instance:

```console
$ php webubbub --request /requests/publish -phub_url=https://a-website.com/topic
```

You can pass multiple arguments by repeating the `-p` flag.

## Requests commands

The requests endpoints are the core of Webubbub. They are the routes called by
the subscribers and publishers. In theory, you should not have to call them,
but it can be handy to test a particular behaviour, or during development.

You can call the `/` path and pass a `-phub_mode=mode` argument where `mode` is
one of these: `subscribe`, `unsubscribe` or `publish`. Or you can call directly
the following commands, without the `-phub_mode` argument.

Please note the expected arguments are the ones [from the WebSub
specification](https://www.w3.org/TR/websub/), but due to a strange PHP
behaviour, the dots (`.`) must be replaced by underscores (`_`).

### Subscribe

```console
$ php webubbub --request /requests/subscribe \
               -phub_callback=https://a-website.com/callback \
               -phub_topic=https://a-website.com/topic
```

You can replace the callback by the `https://websub.flus.io/dummy-subscriber`
URL: it behaves like a subscriber which always accepts WebSub verifications.

You can use a real feed URL for the `hub_topic` URL.

### Unsubscribe

```console
$ php webubbub --request /requests/unsubscribe \
               -phub_callback=https://a-website.com/callback \
               -phub_topic=https://a-website.com/topic
```

The same comments than previously apply to this command.

### Publish

```console
$ php webubbub --request /requests/publish \
               -phub_topic=https://a-website.com/topic
```

The specification doesn’t force the parameter name for publishing, but
indicates that several known implementations accept `hub.url` parameter to
notify the hub of new content. However, the [WebSub validator](https://websub.rocks/)
sends a `hub.topic` parameter. By consequence, Webubbub accepts both arguments.

## Subscriptions

### List

```console
$ php webubbub --request /subscriptions
```

Return the list of known subscriptions with their current status. It can be
very useful to keep an eye on what’s going on on your server.

### Verify

```console
$ php webubbub --request /subscriptions/verify
```

Verify the intents of subscribers by sending them a challenge and checking the
same value is returned.

It is executed by the [`jobs.sh`](../bin/jobs.sh) script and you should never
have to call it directly instead during development.

### Expire

```console
$ php webubbub --request /subscriptions/expire
```

Mark subscriptions which `expired_at` attribute is outdated as `expired`. Such
subscriptions don’t receive content notifications anymore.

It is executed by the [`jobs.sh`](../bin/jobs.sh) script and you should never
have to call it directly instead during development.

## Contents

### List

```console
$ php webubbub --request /contents
```

Return the list of known contents with their current status. It can be very
useful to keep an eye on what’s going on on your server.

### Fetch

```console
$ php webubbub --request /contents/fetch
```

Download the payload of published contents on the hub.

It is executed by the [`jobs.sh`](../bin/jobs.sh) script and you should never
have to call it directly instead during development.

### Deliver

```console
$ php webubbub --request /contents/deliver
```

Deliver the downloaded content to the subscribers and mark the content as
`delivered` only if all the subscribers received it. If it failed, Webubbub
will retry later, up to a max of 7 retries.

It is executed by the [`jobs.sh`](../bin/jobs.sh) script and you should never
have to call it directly instead during development.

## System

These commands are internal to the application. They are called during the
installation of Webubbub, or during its update.

### Init

```console
$ php webubbub --request /system/init
```

Create the database and set the current migration version to the last known
one.

It is aliased by:

```console
$ make init
```

### Migrate

```console
$ php webubbub --request /system/migrate
```

Execute the remaining migrations. A migration automate structural changes that
would be done by the administrator otherwise (e.g. modifying the database structure).

It is aliased by:

```console
$ make migrate
```
