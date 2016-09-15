Deployer Config
===============

## Install
```
composer require-dev git@gitlab.corp.mail.ru:torg/deployer-cfg.git
```
## Usage
#### List of all commands
```
./vendor/bin/deployer
```
#### Generate `main.php` based on production config file
```
./vendor/bin/deployer render -f deploy/torg-front/conf/prod.config.yml -t deploy/torg-front/tmpl/main.php.twig -o ./main.php
```
or
```
./vendor/bin/deployer render -p torg -m front -c prod -t -t deploy/torg-front/tmpl/main.php.twig -o ./main.php
```
#### List of all available configs
```
./vendor/bin/deployer list-configs
```
