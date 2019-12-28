<h1 align="center">Webubbub</h1>

<p align="center">
    <strong>A WebSub hub to bring instant distribution of content between Web actors</strong>
</p>

---

WebSub is an open protocol intended for allowing immediate distribution of
content on the Web. A hub, like Webubbub, is the actor which handles
subscriptions and transfers content from publishers to subscribers.

Today, WebSub is mainly used with Atom and <abbr>RSS</abbr>, but it’s not
limited to those syndication formats.

**This document is intended for administrators who would host a WebSub hub and
for developers who want to improve Webubbub.**

If you’re a publisher (e.g. you run a blog), you might be interested by the
list of known WebSub hubs, [at the end of this document](#known-websub-hubs).

If you’re a subscriber developer (e.g. you work on a syndication aggregator
software), you should take a look [at the <abbr>W3C</abbr> specification](https://www.w3.org/TR/websub/).

If you’re intested in, you can find answers to some questions you might want to
ask [in the <abbr>FAQ</abbr>](docs/faq.md).

## Contributing

At the moment, Webubbub is a personal project of [Marien Fressinaud](https://marienfressinaud.fr/).
It was built to learn WebSub protocol and improve my PHP level. Its direction
is not clear yet and so the code or the architecture might largely evolve in
few days. By consequence, I would not recommend to open pull requests.

However, if you have any question or suggestion, feel free [to open a
ticket](https://github.com/flusio/Webubbub/issues/new) so we can discuss. I’m
looking forward for a better community space and to improve contributing
documentation. More to come later!

## Guides and ressources

1. [How to install Webubbub on your server](docs/production-install.md)
2. [How to update Webubbub](docs/production-update.md)
3. [How to install Webubbub for development](docs/development-install.md)
4. [Learn the command line interface](docs/cli.md)

### WebSub-related ressources

- [websub.rocks validator](https://websub.rocks/)
- [WebSub W3C specification](https://www.w3.org/TR/websub/)
- [WebSub on Wikipedia](https://en.wikipedia.org/wiki/WebSub)
- [WebSub on IndieWeb](https://indieweb.org/WebSub)

### Known WebSub hubs

There are not too many open hubs on the web at the moment:

- [switchboard.p3k.io](https://switchboard.p3k.io/) by [Aaron Parecki](https://aaronparecki.com/);
- [websub.superfeedr.com](https://websub.superfeedr.com/) by [Superfeedr](https://superfeedr.com/) (closed-source);
- [websub.appspot.com](https://websub.appspot.com/) by Google (closed-source).

There are no hubs running on Webubbub yet, unless [websub.flus.io](https://websub.flus.io/)
which will be opened only to customers of the [flus.io service](https://flus.io/).

## License

Webubbub is licensed under [AGPL 3](./LICENSE.txt).
