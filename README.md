elicorege
=========

**E**xtremely **li**ghtweight (PHP) **co**mposer **re**pository **ge**nerator.

Features
--------

* Generates both [packages.json](http://sobstel.org/elicorege/packages.json) and [index.html](http://sobstel.org/elicorege/example.html) info page.
* Uses local repositories to fetch data (extremely fast).

Installation
------------

Download composer.

    wget http://getcomposer.org/composer.phar

Create `composer.json` file.

    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/sobstel/elicorege"
            }
        ],
        "require": {
            "sobstel/elicorege": "*@dev"
        },
        "minimum-stability": "dev"
    }

Install packages.

    php composer.phar install

Usage
-----

    ./vendor/bin/elicorege --config-file=config.json --output-dir=public

config.json
-----------

    {
        "name": "Example",
        "homepage": "http://user:password@git.example.com",

        "base_local_path": "/var/git",
        "base_clone_path": "git@git.example.com",

        "repos": {
            "symfony/classloader": { "relative_path": "mirrors/ClassLoader.git", "references": ["v2.0.16"] },
            "twig": { "relative_path": "mirrors/Twig.git", "references": ["v1.12.3", "v1.12.2"] },
            "lsdcache": { "relative_path": "lsdcache.git", "references": ["master"] }
        }
    }

Limitations
-----------

* All repos must have local copies (eg. `git@github.com:sobstel/elicorege.git` not allowed). It's intentional though as it has advantage of not being vulnerable on external parties downtimes.
