#!/bin/sh
set -eu

bundle=/opt/freshrss-bundle
ext_dest=/var/www/FreshRSS/extensions/xExtension-LanguageCatalog
data=/var/www/FreshRSS/data
root=/var/www/FreshRSS

mkdir -p "$ext_dest" "$data"
cp -a "$bundle/xExtension-LanguageCatalog/." "$ext_dest/"
cp -f "$bundle/config.custom.php" "$data/config.custom.php"
cp -f "$bundle/config-user.custom.php" "$data/config-user.custom.php"

cd "$root"

# 0 = ok, 3 = già fatto. Gli altri codici fermano il container.
run_cli() {
	script=$1
	shift
	set +e
	php -f "$script" -- "$@"
	status=$?
	set -e
	if [ "$status" -eq 0 ] || [ "$status" -eq 3 ]; then
		return 0
	fi
	echo "FreshRSS: $script terminato con codice $status" >&2
	exit "$status"
}

if [ ! -f "$data/applied_migrations.txt" ]; then
	php -f ./cli/prepare.php >/dev/null
	run_cli ./cli/do-install.php \
		--api-enabled \
		--base-url="${BASE_URL}" \
		--db-base="${DB_BASE}" \
		--db-host="${DB_HOST}" \
		--db-password="${DB_PASSWORD}" \
		--db-type=pgsql \
		--db-user="${DB_USER}" \
		--default-user=admin \
		--language=it
fi

if [ -f "$data/config.php" ]; then
	php "$bundle/enable-extension.php"
fi

if [ -z "${ADMIN_PASSWORD:-}" ]; then
	echo "FreshRSS: ADMIN_PASSWORD è vuota. Impostala in Coolify e rifai il deploy." >&2
	exit 1
fi

if [ ! -d "$data/users/admin" ]; then
	echo "FreshRSS: creo l'utente admin"
	run_cli ./cli/create-user.php \
		--user=admin \
		--password="${ADMIN_PASSWORD}" \
		--email="${ADMIN_EMAIL:-}" \
		--api-password="${ADMIN_API_PASSWORD:-}" \
		--language=it \
		--no-default-feeds
else
	echo "FreshRSS: aggiorno email e password di admin"
	run_cli ./cli/update-user.php \
		--user=admin \
		--password="${ADMIN_PASSWORD}" \
		--email="${ADMIN_EMAIL:-}" \
		--api-password="${ADMIN_API_PASSWORD:-}"
fi

# L'entrypoint ufficiale non deve ritentare installazione e utente.
unset FRESHRSS_INSTALL
unset FRESHRSS_USER

# L'immagine predefinita è Debian: cron e apache2, non crond e httpd.
echo "FreshRSS: avvio del server web"
exec "$root/Docker/entrypoint.sh" /bin/sh -c \
	'if [ -n "$CRON_MIN" ]; then /usr/sbin/cron || true; fi; . /etc/apache2/envvars && exec /usr/sbin/apache2 -D FOREGROUND'
