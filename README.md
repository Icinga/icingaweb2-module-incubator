Icinga Web 2 - Incubator
========================

This repository ships bleeding edge libraries useful for Icinga Web 2 modules.
Please download the latest release and install it like any other module.

> **HINT**: Do NOT install the GIT master, it will not work! Checking out a
> branch like `stable/0.18.0` or a tag like `v0.18.0` is fine.

Sample Tarball installation
---------------------------

```sh
MODULE_NAME=incubator
MODULE_VERSION=v0.19.0
MODULES_PATH="/usr/share/icingaweb2/modules"
MODULE_PATH="${MODULES_PATH}/${MODULE_NAME}"
RELEASES="https://github.com/Icinga/icingaweb2-module-${MODULE_NAME}/archive"
mkdir "$MODULE_PATH" \
&& wget -q $RELEASES/${MODULE_VERSION}.tar.gz -O - \
   | tar xfz - -C "$MODULE_PATH" --strip-components 1
icingacli module enable "${MODULE_NAME}"
```

Sample GIT installation
-----------------------

```sh
MODULE_NAME=incubator
MODULE_VERSION=v0.19.0
REPO="https://github.com/Icinga/icingaweb2-module-${MODULE_NAME}"
MODULES_PATH="/usr/share/icingaweb2/modules"
git clone ${REPO} "${MODULES_PATH}/${MODULE_NAME}" --branch "${MODULE_VERSION}"
icingacli module enable "${MODULE_NAME}"
```

Developer Documentation
-----------------------

### Add a new dependency

    composer require author/library:version

### Create a new release

    ./bin/make-release.sh <version>

e.g.

    ./bin/make-release.sh 0.19.0

Changes
-------

### v0.19.0

* improved ProcessInfo serialization
* allow to use Cli\Screen w/o CLI
* curl: fix PHP 8.1 support in specific error conditions
* InfluxDB: fix v2 support
* InfluxDB: body compression
* InfluxDB: add header for debugging purposes

### v0.18.0

* cosmetic changes for Icinga Web
* Settings can now be compared
* fix some zfdb exceptions on 8.1
