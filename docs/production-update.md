# How to update Webubbub (production)

Your Webubbub instance is installed and you’ve got a notification to say that a
new version was released? It’s time to update!

Hopefully, it’ll be easier than the installation :)

The first thing you should absolutely do is to take [a look at the changelog](../CHANGELOG.md)
to know what’s new. Breaking changes and additional migration notes will be
highlighted so you won’t miss them. Even if we try to automate a maximum of
actions, **it’s very important to not ignore this step.** We might introduce
new required environment variables, or rename some for instance.

Once you know what are the additional steps, you can proceed to the update.

First, fetch the new code with Git:

```console
$ git fetch --recurse-submodules
$ git fetch --tags
```

Verify you didn’t change anything by yourself with:

```console
$ git status
```

If you did, you absolutely must remove them with:

```console
$ git clean -df
$ git checkout -- .
```

This should never happen unless you know what you do, but always prefer [to
open a ticket](https://github.com/flusio/Webubbub/issues/new) to ask if your
changes can be merged upstream.

Now, switch to [the latest version](https://github.com/flusio/Webubbub/releases/latest)
with:

```console
$ git checkout <latest version tag>
```

Make sure files ownership is still correct:

```console
$ sudo chown -R www-data:www-data .
```

And run the migrations:

```console
$ php cli migrations setup --seed
```

Finally, restart the jobs worker:

```console
$ sudo systemctl restart webubbub.service
```

Everything should be fine. If an error happens, please open a ticket so we can
help you. **And don’t forget to apply the potential additional indications from
the changelog!**

Optional last step that might be useful: verify your instance is still online.
