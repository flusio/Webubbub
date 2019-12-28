# Frequently (and less frequently) Asked Questions

## Are <abbr>RSS</abbr> and Atom obsolete?

Once upon a time, there were two open widely-used formats to allow applications
to access updates to Web content: <abbr>RSS</abbr> and Atom. People were
arguing which one was the best, but all things considered, they were both quite
good and equivalent.

These formats allowed open access to content published on the Web. They were
easy to set up, easy to understand and based on standard and interoperable
technologies. They gave people a solid ground for sharing knowledge and
contents for free. All was good at this time.

Unfortunately, few years later, customs had changed and people moved to
instantaneous (closed) platforms such as Twitter. Content published on the Web
became progressively harder to fetch, closing it in big silos. Attempts from
the open-source community to answer these new habits were often to create new
protocols, harder to implement and to maintain, sometimes only mimicing
behaviour of closed-source platforms. Our good old formats, Atom and
<abbr>RSS</abbr> began to fade from our collective memory. Even one of the
biggest advocate of the open Web, Mozilla, [removed support of syndication
feeds from Firefox](https://support.mozilla.org/en-US/kb/feed-reader-replacements-firefox).
So sad.

But was Atom really obsolete? Was <abbr>RSS</abbr> dead? Nah, obviously not and
here’s why!

The new protocols were often harder to understand, gaving more power to few
experts, damaging in the same time the share of knowledge. The open Web was
still open, but only to some people who had time to learn these new
technologies. This was not about ranting at these protocols: they often
answered to needs that Atom and <abbr>RSS</abbr> weren’t able to fulfill by
themselves; they were only formats after all. This was about thinking how to
answer to a need with the simplest solution that could be understood and
implemented by as many actors as possible.

It was not about technologies, it was about getting back the power of users on
their own data. Closed-source platforms were their common ennemies.

But years passed and, today, Atom and <abbr>RSS</abbr> are still not able to
regain the heart of their users, even with the help of their new friend [JSON
Feed](https://jsonfeed.org/). They are still great to give free access
to Web content though, and to break proprietary silos… if not the greatest.

But the battle is not over! Will you contribute to the open Web and simple
technologies? And remember: technologies are not an end in themselves, its
about usage and giving power to people over their own data.

## Why is WebSub important for an open Web?

As said before, Atom and <abbr>RSS</abbr> are only formats. An Atom file has to
be fetched every X minutes, even if new content hasn’t be published. It
generates useless traffic and, sometimes, publishers even (legitimately) block
subscribers if they try too often.

If this system is good enough for a blog which publish non-critical information
once in a while, users may want to be noticed about new content immediately
after being published. It could be very useful for a feed of system alerts for
instance. Instant notifications are also widely expected from a user
perspective on modern platforms. This lack is certainly one of the reason why
Atom and <abbr>RSS</abbr> lost of their interest over time.

This is where WebSub turns to be interesting. It adds an optional sligth layer
of complexity over Atom and <abbr>RSS</abbr> to support real-time notifications.
New content is directly brought from publishers to subscribers without any
delay. It is important to note that the protocol is separated from content:
syndication formats stay simple as they were before. Complexity is only added
if you need to or if you want to; the content stays as accessible as before.
It’s welcoming for new comers who want to learn syndication technologies step
by step.

WebSub can also be used with any kind of content (text, images, even videos),
the publisher only has to declare two links in the <abbr>HTTP</abbr> header:
the `self` link refers to the current document, the `hub` one points to the hub
which will get and distribute the document.

## How is WebSub complicated?

Complexity is added gradually over actors who have from less to more interests
in the protocol.

A publisher just wants to care about its content, not about its distribution.
Its action remains as simple as to choose an open hub, to add a `hub` link to
its feed and to send a notification to the hub when it publishes an article.

A subscriber is interested into getting its content as soon as possible, and so
should make more efforts. It has to get `hub` links from the feeds and to
request a subscription to the concerned hubs. It also has to verify itself when
a hub asks for it and renew its subscriptions before the end of the lease
timeouts. It eventually might want to check integrity of content sent by the
hub.

The hub is more probably advocating for WebSub, and so it’s the actor who has
the more to do. It receives (un)subscription requests, validates and
verifies them. Then, it has to get publishing notifications, to fetch the
content and to send it to the subscribers, while keeping a trace of what
happened during the process (especially the errors).

Note that a publisher or subscriber are not the end users. A publisher can be a
blog platform, automating the notifications distribution to a hub for the human
writer. A subscriber can be a syndication aggregator which hide the process of
subscription and content reception from the human reader.

## Why is it important to host your hub?

The hubs store the relation between a subscriber and a publisher. While before
the relation was direct, with WebSub they both need to trust the hub maintainer
for not spying who subscribed to what. For instance, one of the actual two
best-known open hubs is hosted by Google, who proved itself to not be always
trustworthy.

Having only a few of WebSub hubs concentrate power (i.e. knowledge) in the
hands of some structures. This is bad for the open and distributed Web that we
dream of.

If you’re a publisher with technical skills, it’s best to host your own WebSub
server so the relation you have with your subscriber stays direct, without any
third-parties. If you can’t or don’t want to, you should choose your hub
carefully. You also can ask someone you trust to host a hub for you and some of
your friends.
