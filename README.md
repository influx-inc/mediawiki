# 🔥 Abandon hope, all ye who enter here... 🔥

This a fork of the Mediawiki repo:

- `master` is an unmodified clone of the upstream master.
- `influx` is the branch we deploy from: with skin and extensions, plus some customizations for Heroku deployment.

Note this repo is public! (make sure you don't commit any secrets).

We're using bleeding edge instead of the latest stable release (1.37.2) because the latest versions of PHP/composer broke compatibility with 1.37 🤪

## Installation

On OSX via Homebrew:

    # PHP 8:
    brew tap shivammathur/php
    brew install shivammathur/php/php@8.0
    brew link --overwrite --force php@8.0
    brew install composer

    # Mysql:
    brew install mysql

    # Checkout the influx branch:
    git clone https://github.com/influx-inc/mediawiki
    cd mediawiki
    git checkout influx

    # Install PHP dependencies:
    compose install --no-dev
    
See `LocalSettings.php` for configuration.

## Deploy

(currently Heroku/Github integration is broken)

Push the `influx` branch to Heroku `master`:

    git push heroku influx:master

## Upgrade

**In theory**, this should be a simple as fetching upstream commits to master and rebasing the Influx branch.

    git checkout master
    git rebase
    git checkout influx
    git rebase master
    
To run database migrations:

    php maintenance/update.php
    
I highly recommend testing this in a dev environment before attempting it on production.




