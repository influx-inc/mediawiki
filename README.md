## Branches

- `master` is a clone of the upstream master.
- `influx` is a fork of master with our extensions installed, prepped for Heroku deployment.

We're using bleeding edge instead of the latest stable release (1.37.2) because the latest versions of PHP /composer broke compatibility with 1.37 🤪

# Notes

- Heroku app name: `influx-wiki`
- Assets are stored in S3 bucket: `influx-wiki`
- See `LocalSettings.php` for configuration.

## Installation

Via Homebrew:

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
