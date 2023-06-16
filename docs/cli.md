# Learn the command line interface (CLI)

Webubbub provides a handy tool to test and manipulate what’s going on in the
application. If you have any doubt, you can run the following command:

```console
$ php cli help
```

## Requests commands

The requests endpoints are the core of Webubbub. They are the routes called by
the subscribers and publishers. In theory, you should not have to call them,
but it can be handy to test a particular behaviour, or during development.

Please note the expected arguments are the ones [from the WebSub
specification](https://www.w3.org/TR/websub/), but due to a strange PHP
behaviour, the dots (`.`) must be replaced by underscores (`_`).

### Subscribe

```console
$ php cli requests subscribe \
    --hub_callback=https://a-website.com/callback \
    --hub_topic=https://a-website.com/topic
```

You can replace the callback by the `https://websub.flus.io/dummy-subscriber`
URL: it behaves like a subscriber which always accepts WebSub verifications.

You can use a real feed URL for the `hub_topic` URL.

### Unsubscribe

```console
$ php cli requests unsubscribe \
    --hub_callback=https://a-website.com/callback \
    --hub_topic=https://a-website.com/topic
```

The same comments than previously apply to this command.

### Publish

```console
$ php cli requests publish --hub_topic=https://a-website.com/topic
```

The specification doesn’t force the name of the parameter for publishing, but
indicates that several known implementations accept `hub.url` parameter to
notify the hub of new content. However, the [WebSub validator](https://websub.rocks/)
sends a `hub.topic` parameter. By consequence, Webubbub accepts both arguments.

## List the subscriptions

```console
$ php cli subscriptions
```

Return the list of known subscriptions with their current status. It can be
very useful to keep an eye on what’s going on on your server.

## List the contents

```console
$ php cli contents
```

Return the list of known contents with their current status. It can be very
useful to keep an eye on what’s going on on your server.
