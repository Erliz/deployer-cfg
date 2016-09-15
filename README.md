Deployer Config
===============

## Install
```
php composer.phar -g config repositories.deploy-cfg vcs git@gitlab.corp.mail.ru:torg/deployer-cfg.git
php composer.phar global require torg/deployer-cfg
```
#### Add shortcut
you can add global `vendor/bin` to bash `$PATH`
```
echo 'export PATH="$PATH:~/.composer/vendor/bin/"' >> ~/.bash_profile
```
reload bash
```
deployer-cfg list
```
## Update
```
php composer.phar global update
```
## Usage
#### List of all commands
```
~/.composer/vendor/bin/deployer-cfg
```
#### Generate `main.php` based on production config file
```
~/.composer/vendor/bin/deployer-cfg render -f deploy/torg-front/conf/prod.config.yml -t deploy/torg-front/tmpl/main.php.twig -o ./main.php
```
or
```
~/.composer/vendor/bin/deployer-cfg render -p torg -m front -c prod -t -t deploy/torg-front/tmpl/main.php.twig -o ./main.php
```
#### List of all available configs
```
~/.composer/vendor/bin/deployer-cfg list-configs
```
