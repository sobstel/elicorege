elicorege
=========

**E**xtremely **l**ightweight (PHP) **co**mposer **re**pository **ge**nerator.

Features
--------

* Generates both packages.json and index.html info page.
* Uses local repositories to fetch data (extremely fast).

Usage
-----

    ./bin/elicorege --config-file=config.json --output-dir=public

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
