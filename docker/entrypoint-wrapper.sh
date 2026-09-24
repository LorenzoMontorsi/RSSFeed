#!/bin/sh
set -eu

bundle=/opt/freshrss-bundle
ext_dest=/var/www/FreshRSS/extensions/xExtension-LanguageCatalog
data=/var/www/FreshRSS/data

mkdir -p "$ext_dest" "$data"
cp -a "$bundle/xExtension-LanguageCatalog/." "$ext_dest/"
cp -f "$bundle/config.custom.php" "$data/config.custom.php"
cp -f "$bundle/config-user.custom.php" "$data/config-user.custom.php"

# Su un'installazione già fatta, config.custom.php non viene riletto.
# Il patch riattiva l'estensione nel config.php esistente.
if [ -f "$data/config.php" ]; then
	php "$bundle/enable-extension.php"
fi

exec /var/www/FreshRSS/Docker/entrypoint.sh "$@"
