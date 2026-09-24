<?php

declare(strict_types=1);

/**
 * Riattiva LanguageCatalog in un config.php già scritto dall'installazione.
 * Al primo avvio il file non esiste ancora: ci pensa config.custom.php.
 */

$path = '/var/www/FreshRSS/data/config.php';
if (!is_file($path)) {
	exit(0);
}

$config = include $path;
if (!is_array($config)) {
	fwrite(STDERR, "LanguageCatalog: data/config.php non è un array\n");
	exit(1);
}

$enabled = $config['extensions_enabled'] ?? [];
if (!is_array($enabled)) {
	$enabled = [];
}

if (($enabled['LanguageCatalog'] ?? false) === true) {
	exit(0);
}

$enabled['Google-Groups'] = $enabled['Google-Groups'] ?? true;
$enabled['Tumblr-GDPR'] = $enabled['Tumblr-GDPR'] ?? true;
$enabled['LanguageCatalog'] = true;
$config['extensions_enabled'] = $enabled;

$export = var_export($config, true);
if (!is_string($export)) {
	fwrite(STDERR, "LanguageCatalog: impossibile esportare config.php\n");
	exit(1);
}

$written = file_put_contents($path, "<?php\nreturn {$export};\n", LOCK_EX);
if ($written === false) {
	fwrite(STDERR, "LanguageCatalog: impossibile scrivere config.php\n");
	exit(1);
}

fwrite(STDOUT, "LanguageCatalog abilitata nel config.php esistente\n");
