<?php

declare(strict_types=1);

/**
 * Impostazioni di sistema lette solo alla prima installazione (CLI).
 * Le estensioni di sistema già presenti nell'immagine ufficiale restano attive.
 */
return [
	'language' => 'it',
	'title' => 'Notizie',
	'extensions_enabled' => [
		'Google-Groups' => true,
		'Tumblr-GDPR' => true,
		'LanguageCatalog' => true,
	],
];
