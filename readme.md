# Scrub Old Posts [![Build Status](https://travis-ci.org/trepmal/scrub-old-posts.svg?branch=master)](https://travis-ci.org/trepmal/scrub-old-posts)

Remove content older than a given date.

## Installation

Recommended:

`wp package install trepmal/scrub-old-posts`

## Synopsis

`wp scrub posts --date=<date> [--post_type=<post_type>] [--posts_per_page=<num>] [--dry-run]`

## Options

    --date=<date>
      Delete posts older than this date.

    [--post_type=<post_type>]
      Post type. Default: post

    [--posts_per_page=<num>]
      Proccess in batches of <num>. Default: 100

    [--dry-run]
      Dry run.

    [--yes]
      Answer "yes" to confirmation.

## Examples

    wp scrub posts --date='-1 month'
    wp scrub posts --date='2015-01-01'
