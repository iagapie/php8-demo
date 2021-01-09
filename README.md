# Demo App

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892BF.svg)](https://php.net/)

## Installation

Install docker and docker compose.
 * MacOS - [Docker](https://docs.docker.com/docker-for-mac/install/)
 * Linux:
    * Ubuntu [docker](https://docs.docker.com/engine/install/ubuntu/)
    * [docker compose](https://docs.docker.com/compose/install/)

```bash
$ cp .env.dist .env

$ # MacOS
$ sed -i '' "s/USER_ID=.*/USER_ID=$(id -u)/g" .env
$ sed -i '' "s/GROUP_ID=.*/GROUP_ID=$(id -g)/g" .env

$ # Linux
$ sed -i "s/USER_ID=.*/USER_ID=$(id -u)/g" .env
$ sed -i "s/GROUP_ID=.*/GROUP_ID=$(id -g)/g" .env

$ make start
$ make install
```

### Demo

http://localhost

### PhpMyAdmin

http://localhost:8080
* Username: `root`
* Password: `password`

### Generate a migration

```bash
$ make cli
$ bin/console migrations:generate
```