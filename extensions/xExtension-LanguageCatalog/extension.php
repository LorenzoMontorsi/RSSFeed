<?php

declare(strict_types=1);

/**
 * Cataloga gli articoli nuovi con un'etichetta per lingua.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once __DIR__ . '/LanguageDetector.php';

final class LanguageCatalogExtension extends Minz_Extension {
	private const TAG_PREFIX = 'lingua-';

	/** @var array<string, true> */
	private array $ensured = [];

	#[\Override]
	public function init(): void {
		$this->registerHook('entry_before_insert', [$this, 'tagLanguage']);

		if (!class_exists('FreshRSS_Context') || !FreshRSS_Context::hasUserConf()) {
			return;
		}
		if ($this->italianLabelExists()) {
			return;
		}

		foreach (LanguageDetector::catalog() as $code => $name) {
			$this->ensureLabel($code, $name);
		}
	}

	/**
	 * Una query sola: se l'etichetta Italiano è già nostra, il catalogo è stato creato.
	 */
	private function italianLabelExists(): bool {
		if (!class_exists('FreshRSS_Factory')) {
			return false;
		}
		try {
			$tagDAO = FreshRSS_Factory::createTagDao();
			$existing = $tagDAO->searchByName('Italiano');
			if ($existing !== null && $existing->attributeString('language_catalog') === 'it') {
				$this->ensured['it'] = true;
				return true;
			}
			$existing = $tagDAO->searchByName('Lingua · Italiano');
			if ($existing !== null && $existing->attributeString('language_catalog') === 'it') {
				$this->ensured['it'] = true;
				return true;
			}
		} catch (Throwable $exception) {
			if (class_exists('Minz_Log')) {
				Minz_Log::warning('LanguageCatalog: ' . $exception->getMessage());
			}
			return true;
		}
		return false;
	}

	/**
	 * Aggiunge il tag lingua-* prima del salvataggio.
	 * FreshRSS applica poi l'etichetta il cui filtro corrisponde a quel tag.
	 */
	public function tagLanguage(FreshRSS_Entry $entry): FreshRSS_Entry {
		try {
			$text = $entry->title() . "\n" . $entry->content();
			$code = LanguageDetector::detect($text);
			if ($code === null) {
				return $entry;
			}

			$tags = $entry->tags();
			if (!is_array($tags)) {
				$tags = [];
			}
			$tags = array_values(array_filter(
				$tags,
				static fn (mixed $tag): bool => is_string($tag) && !str_starts_with($tag, self::TAG_PREFIX)
			));
			$tags[] = self::TAG_PREFIX . $code;
			$entry->_tags($tags);
			$entry->_attribute('LanguageCatalog', ['code' => $code]);

			$this->ensureLabel($code, LanguageDetector::labelName($code));
		} catch (Throwable $exception) {
			if (class_exists('Minz_Log')) {
				Minz_Log::warning('LanguageCatalog: ' . $exception->getMessage());
			}
		}

		return $entry;
	}

	private function ensureLabel(string $code, string $name): void {
		if (isset($this->ensured[$code])) {
			return;
		}
		$this->ensured[$code] = true;

		if (!class_exists('FreshRSS_Factory') || !class_exists('FreshRSS_Tag')) {
			return;
		}

		try {
			$tagDAO = FreshRSS_Factory::createTagDao();
			$chosen = $this->availableLabelName($tagDAO, $code, $name);
			if ($chosen === null) {
				return;
			}

			$created = $this->insertLabel($tagDAO, $chosen, $code);
			if ($created === false && !str_starts_with($chosen, 'Lingua · ')) {
				$created = $this->insertLabel($tagDAO, 'Lingua · ' . $chosen, $code);
			}
			if ($created === false && class_exists('Minz_Log')) {
				Minz_Log::warning('LanguageCatalog: etichetta non creata per ' . $code);
			}
		} catch (Throwable $exception) {
			if (class_exists('Minz_Log')) {
				Minz_Log::warning('LanguageCatalog: ' . $exception->getMessage());
			}
		}
	}

	private function insertLabel(FreshRSS_TagDAO $tagDAO, string $name, string $code): int|false {
		$tag = new FreshRSS_Tag($name);
		$tag->_filtersAction('label', ['#' . self::TAG_PREFIX . $code]);
		$tag->_attribute('language_catalog', $code);
		return $tagDAO->addTag([
			'name' => $tag->name(),
			'attributes' => $tag->attributes(),
		]);
	}

	/**
	 * Non riusa un'etichetta creata a mano con lo stesso nome.
	 */
	private function availableLabelName(FreshRSS_TagDAO $tagDAO, string $code, string $name): ?string {
		$existing = $tagDAO->searchByName($name);
		if ($existing === null) {
			return $name;
		}
		if ($existing->attributeString('language_catalog') === $code) {
			return null;
		}

		$prefixed = 'Lingua · ' . $name;
		$existing = $tagDAO->searchByName($prefixed);
		if ($existing === null) {
			return $prefixed;
		}
		if ($existing->attributeString('language_catalog') === $code) {
			return null;
		}

		return null;
	}
}
