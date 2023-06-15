# How to install Webubbub (development)

First, download Webubbub:

```console
$ git clone --recurse-submodules https://github.com/flusio/Webubbub.git
$ cd Webubbub
```

## Run the application

### With Docker

We provide a useful Docker configuration to avoid problems due to a bad
configuration and to allow developers to be quickly efficient. You don’t need
to know about Docker to develop, but you should install it on your PC, along
with docker-compose:

- [Instructions for Docker](https://docs.docker.com/engine/install/)
- [Instructions for Docker Compose](https://docs.docker.com/compose/install/)

First, install the development dependencies:

```console
$ make install
```

You can start the application with:

```console
$ make docker-start
```

It will download and start a `nginx` and a `php` containers. The Nginx server
is listening on [localhost:8000](http://localhost:8000). The servers are not
run in daemon mode so you can keep an eye on the received requests and logs.

### Local Nginx

If you already have a Nginx server running on your computer with PHP, you can
use it for development. Please note the server should run on port 8000. You
should also verify it’s correctly configured. Go to [this URL](http://localhost:8000/dummy-subscriber?hub.challenge=foo)
and check the string `foo` is displayed.

To install the development dependencies, run:

```console
$ make install NODOCKER=true
```

If it doesn’t work, please compare your actual configuration [with the one used
with Docker](../docker/nginx.conf).

You’re very welcome if you want to help us to complete this section of
documentation!

## Initializing the application

Then, you must initialize the database with:

```console
$ make init
$ # or if you don't use Docker
$ make init NODOCKER=true
```

And, you’re done!
