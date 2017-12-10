# Veyring Frontend

Please install first "docker" and "docker compose"
It is beneficial if the host has PHP 7.1 installed, otherwise
is possible to perform every operation bashing into the
container but is much simpler to have PHP 7.1 installed
in the host machine.

```
Composer install
```
will install all the required dependencies
but there might be need of changing permissions to some folders.

A file must be created for the environment, to get things to start
is just fine to copy .env.dist into .env


Once running:
```
sudo doocker-compose up
```