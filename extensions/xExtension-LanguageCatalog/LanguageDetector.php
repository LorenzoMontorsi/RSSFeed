<?php

declare(strict_types=1);

/**
 * Rilevamento della lingua senza servizi esterni.
 * Prima guarda l'alfabeto, poi le parole funzionali più tipiche di ogni lingua.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
final class LanguageDetector {
	/**
	 * Etichette mostrate nel menu di FreshRSS, nell'ordine del catalogo.
	 *
	 * @var array<string, string>
	 */
	private const CATALOG = [
		'it' => 'Italiano',
		'en' => 'English',
		'fr' => 'Français',
		'de' => 'Deutsch',
		'es' => 'Español',
		'pt' => 'Português',
		'nl' => 'Nederlands',
		'pl' => 'Polski',
		'ro' => 'Română',
		'sv' => 'Svenska',
		'da' => 'Dansk',
		'no' => 'Norsk',
		'fi' => 'Suomi',
		'cs' => 'Čeština',
		'sk' => 'Slovenčina',
		'hu' => 'Magyar',
		'hr' => 'Hrvatski',
		'ca' => 'Català',
		'el' => 'Ελληνικά',
		'tr' => 'Türkçe',
		'ru' => 'Русский',
		'uk' => 'Українська',
		'bg' => 'Български',
		'sr' => 'Српски',
		'ar' => 'العربية',
		'fa' => 'فارسی',
		'he' => 'עברית',
		'zh' => '中文',
		'ja' => '日本語',
		'ko' => '한국어',
	];

	/**
	 * Peso per occorrenza. Le parole condivise tra lingue vicine pesano meno.
	 *
	 * @var array<string, array<string, int>>
	 */
	private const WORDS = [
		'it' => [
			'della' => 5, 'delle' => 5, 'degli' => 5, 'dello' => 5, 'nell' => 4, 'nella' => 5, 'nelle' => 5,
			'questo' => 4, 'questa' => 4, 'questi' => 4, 'anche' => 4, 'più' => 5, 'perché' => 5, 'sono' => 3,
			'essere' => 2, 'stato' => 2, 'oggi' => 4, 'lavoro' => 3, 'imprese' => 4, 'vigore' => 4, 'entro' => 3,
			'riguarderà' => 4, 'entrerà' => 3, 'governo' => 2, 'decreto' => 2, 'modifica' => 3, 'regole' => 4,
			'senza' => 2, 'ancora' => 3, 'sempre' => 2, 'dopo' => 2, 'prima' => 2, 'quando' => 2, 'quindi' => 4,
			'gli' => 4, 'che' => 1, 'non' => 1, 'per' => 1, 'con' => 1, 'una' => 1, 'del' => 1, 'della' => 5,
			'suoi' => 3, 'sua' => 2, 'già' => 4, 'così' => 4, 'dove' => 2, 'ogni' => 3, 'altri' => 2, 'tra' => 2,
			'fra' => 3, 'ed' => 2, 'ha' => 1, 'il' => 2, 'lo' => 1, 'la' => 1, 'un' => 1, 'le' => 2, 'è' => 4,
		],
		'en' => [
			'the' => 5, 'and' => 3, 'that' => 3, 'with' => 3, 'from' => 3, 'this' => 3, 'have' => 2, 'which' => 4,
			'would' => 4, 'their' => 4, 'about' => 3, 'after' => 2, 'before' => 3, 'people' => 2, 'been' => 4,
			'into' => 3, 'than' => 3, 'also' => 2, 'were' => 4, 'was' => 3, 'are' => 2, 'for' => 2, 'not' => 1,
			'you' => 2, 'his' => 2, 'her' => 2, 'she' => 3, 'they' => 4, 'will' => 3, 'according' => 4,
			'government' => 3, 'approved' => 3, 'workers' => 3, 'companies' => 3, 'month' => 2, 'today' => 2,
			'of' => 2, 'to' => 1, 'in' => 1, 'a' => 1, 'is' => 1,
		],
		'fr' => [
			'les' => 4, 'des' => 4, 'dans' => 4, 'pour' => 2, 'qui' => 2, 'sur' => 3, 'pas' => 3, 'avec' => 3,
			'sont' => 3, 'cette' => 4, 'nous' => 3, 'vous' => 3, 'aux' => 4, 'elle' => 2, 'ils' => 3, 'été' => 4,
			'aussi' => 3, 'entre' => 2, 'où' => 5, 'ces' => 3, 'leur' => 3, 'aujourd' => 5, 'hui' => 3,
			'gouvernement' => 4, 'approuvé' => 4, 'projet' => 3, 'modifie' => 3, 'règles' => 4, 'travail' => 3,
			'selon' => 3, 'entrera' => 3, 'vigueur' => 3, 'avant' => 3, 'concernera' => 5, 'petites' => 3,
			'entreprises' => 4, 'est' => 2, 'une' => 2, 'du' => 2, 'le' => 2, 'la' => 1, 'un' => 1, 'et' => 1,
			'que' => 1, 'en' => 1,
		],
		'de' => [
			'der' => 3, 'die' => 3, 'und' => 2, 'den' => 3, 'von' => 2, 'das' => 3, 'mit' => 2, 'sich' => 3,
			'auf' => 2, 'für' => 4, 'ist' => 2, 'nicht' => 3, 'ein' => 2, 'eine' => 3, 'auch' => 2, 'dass' => 4,
			'nach' => 2, 'wird' => 3, 'bei' => 3, 'sind' => 2, 'noch' => 3, 'über' => 4, 'zum' => 3, 'zur' => 3,
			'haben' => 2, 'heute' => 3, 'gesetz' => 4, 'beschlossen' => 5, 'regeln' => 3, 'arbeit' => 3,
			'ändert' => 4, 'angaben' => 4, 'ministers' => 3, 'tritt' => 4, 'maßnahme' => 5, 'kraft' => 3,
			'betrifft' => 4, 'kleinere' => 4, 'unternehmen' => 4, 'regierung' => 4, 'neues' => 3, 'des' => 2,
			'im' => 2, 'dem' => 2, 'hat' => 1,
		],
		'es' => [
			'los' => 4, 'las' => 4, 'del' => 2, 'por' => 2, 'para' => 3, 'como' => 2, 'más' => 5, 'pero' => 3,
			'sus' => 3, 'este' => 2, 'esta' => 2, 'también' => 5, 'sobre' => 3, 'según' => 5, 'desde' => 3,
			'hasta' => 3, 'donde' => 3, 'muy' => 3, 'todos' => 3, 'puede' => 3, 'gobierno' => 3, 'aprobó' => 5,
			'hoy' => 3, 'cambia' => 3, 'reglas' => 4, 'trabajo' => 3, 'entrará' => 4, 'vigor' => 2, 'antes' => 3,
			'termine' => 3, 'afectará' => 5, 'empresas' => 4, 'pequeñas' => 4, 'decreto' => 2, 'nuevo' => 2,
			'que' => 1, 'una' => 1, 'con' => 1, 'el' => 2, 'la' => 1, 'un' => 1, 'y' => 1, 'en' => 1,
		],
		'pt' => [
			'não' => 5, 'para' => 2, 'como' => 2, 'mais' => 3, 'dos' => 3, 'das' => 4, 'pelo' => 4, 'pela' => 4,
			'foi' => 3, 'são' => 4, 'está' => 4, 'também' => 4, 'quando' => 2, 'até' => 4, 'onde' => 2,
			'muito' => 4, 'seu' => 2, 'sua' => 2, 'eles' => 3, 'já' => 4, 'ainda' => 4, 'depois' => 3,
			'governo' => 2, 'aprovou' => 5, 'hoje' => 4, 'novo' => 2, 'muda' => 3, 'regras' => 3, 'trabalho' => 3,
			'vai' => 4, 'entrar' => 3, 'vigor' => 2, 'antes' => 2, 'mês' => 4, 'afetar' => 4, 'empresas' => 3,
			'menores' => 4, 'segundo' => 2, 'que' => 1, 'uma' => 1, 'com' => 1, 'um' => 2, 'os' => 2, 'as' => 2,
			'do' => 2, 'da' => 2, 'o' => 1, 'a' => 1, 'e' => 1, 'em' => 1,
		],
		'nl' => [
			'het' => 4, 'een' => 3, 'van' => 2, 'dat' => 3, 'voor' => 3, 'met' => 2, 'zijn' => 3, 'niet' => 3,
			'aan' => 3, 'ook' => 2, 'maar' => 2, 'bij' => 3, 'nog' => 2, 'naar' => 3, 'over' => 2, 'werd' => 4,
			'worden' => 4, 'heeft' => 4, 'deze' => 3, 'geen' => 4, 'wel' => 2, 'regering' => 4, 'vandaag' => 4,
			'nieuwe' => 3, 'wet' => 3, 'goedgekeurd' => 5, 'regels' => 3, 'werk' => 2, 'verandert' => 4,
			'volgens' => 4, 'minister' => 2, 'treedt' => 5, 'maatregel' => 4, 'einde' => 3, 'maand' => 3,
			'werking' => 4, 'geldt' => 4, 'kleinere' => 3, 'bedrijven' => 4, 'de' => 2, 'en' => 1, 'in' => 1,
			'is' => 1, 'op' => 1, 'te' => 1,
		],
		'pl' => [
			'się' => 5, 'nie' => 2, 'jest' => 2, 'jak' => 2, 'tylko' => 3, 'jego' => 3, 'przez' => 4, 'przy' => 3,
			'dla' => 3, 'oraz' => 5, 'został' => 5, 'została' => 5, 'który' => 4, 'która' => 4, 'które' => 4,
			'rząd' => 4, 'przyjął' => 5, 'dziś' => 4, 'nową' => 4, 'ustawę' => 5, 'która' => 4, 'zmienia' => 3,
			'zasady' => 4, 'pracy' => 3, 'według' => 4, 'ministra' => 3, 'przepis' => 4, 'wejdzie' => 4,
			'życie' => 3, 'końcem' => 4, 'miesiąca' => 4, 'obejmie' => 5, 'także' => 4, 'mniejsze' => 4,
			'firmy' => 3, 'na' => 1, 'do' => 1, 'że' => 3, 'to' => 1, 'po' => 1, 'za' => 1, 'od' => 1,
		],
		'ro' => [
			'și' => 5, 'este' => 3, 'sunt' => 3, 'pentru' => 4, 'sau' => 2, 'care' => 2, 'fost' => 3, 'după' => 4,
			'când' => 4, 'acest' => 3, 'această' => 4, 'până' => 4, 'decât' => 5, 'însă' => 5, 'dacă' => 4,
			'guvernul' => 4, 'astăzi' => 4, 'nouă' => 3, 'lege' => 2, 'schimbă' => 4, 'regulile' => 4,
			'muncii' => 4, 'potrivit' => 4, 'ministrului' => 4, 'măsura' => 4, 'intra' => 2, 'vigoare' => 4,
			'sfârșitul' => 5, 'lunii' => 4, 'afecta' => 3, 'firmele' => 4, 'mici' => 2, 'nu' => 2, 'din' => 2,
			'pe' => 1, 'cu' => 1, 'de' => 1, 'la' => 1, 'în' => 2, 'o' => 1,
		],
		'sv' => [
			'och' => 2, 'att' => 3, 'det' => 2, 'som' => 2, 'för' => 3, 'den' => 2, 'till' => 2, 'har' => 2,
			'inte' => 4, 'från' => 4, 'också' => 4, 'efter' => 2, 'eller' => 3, 'när' => 3, 'över' => 3,
			'regeringen' => 4, 'godkände' => 5, 'idag' => 4, 'nytt' => 3, 'lagförslag' => 5, 'ändrar' => 4,
			'reglerna' => 4, 'arbete' => 3, 'enligt' => 4, 'ministern' => 4, 'åtgärden' => 5, 'träder' => 5,
			'kraft' => 2, 'före' => 3, 'slutet' => 3, 'månaden' => 4, 'påverkar' => 4, 'mindre' => 3,
			'företag' => 4, 'en' => 1, 'på' => 1, 'är' => 2, 'av' => 1, 'med' => 1, 'de' => 1, 'om' => 1,
		],
		'da' => [
			'og' => 1, 'at' => 2, 'det' => 2, 'til' => 2, 'som' => 2, 'for' => 1, 'ikke' => 4, 'eller' => 2,
			'også' => 3, 'regeringen' => 3, 'godkendte' => 5, 'dag' => 2, 'nyt' => 2, 'lovforslag' => 5,
			'ændrer' => 5, 'reglerne' => 4, 'arbejde' => 3, 'ifølge' => 5, 'ministeren' => 4, 'tiltaget' => 5,
			'træder' => 5, 'kraft' => 2, 'inden' => 4, 'udgangen' => 5, 'måneden' => 3, 'påvirker' => 4,
			'mindre' => 2, 'virksomheder' => 5, 'en' => 1, 'på' => 1, 'er' => 1, 'af' => 1, 'med' => 1,
			'den' => 1, 'har' => 1, 'de' => 1,
		],
		'no' => [
			'og' => 1, 'det' => 2, 'til' => 2, 'som' => 2, 'ikke' => 4, 'eller' => 2, 'også' => 3, 'ble' => 4,
			'være' => 3, 'regjeringen' => 5, 'godkjente' => 5, 'dag' => 2, 'nytt' => 2, 'lovforslag' => 5,
			'endrer' => 4, 'reglene' => 4, 'arbeid' => 3, 'ifølge' => 4, 'ministeren' => 3, 'tiltaket' => 5,
			'trer' => 4, 'kraft' => 2, 'før' => 3, 'utgangen' => 5, 'måneden' => 3, 'påvirker' => 3,
			'mindre' => 2, 'bedrifter' => 5, 'en' => 1, 'på' => 1, 'er' => 1, 'av' => 1, 'med' => 1,
			'den' => 1, 'har' => 1, 'de' => 1, 'for' => 1,
		],
		'fi' => [
			'että' => 4, 'mutta' => 4, 'kun' => 2, 'niin' => 3, 'tai' => 2, 'jos' => 3, 'hän' => 4, 'myös' => 4,
			'vain' => 3, 'kuin' => 3, 'ovat' => 3, 'ollut' => 4, 'mukaan' => 4, 'jälkeen' => 4, 'ennen' => 3,
			'kanssa' => 4, 'ilman' => 4, 'sekä' => 5, 'vielä' => 4, 'hallitus' => 4, 'hyväksyi' => 5, 'tänään' => 5,
			'uuden' => 4, 'lain' => 3, 'muuttaa' => 4, 'työn' => 4, 'sääntöjä' => 5, 'ministerin' => 4,
			'toimenpide' => 5, 'tulee' => 3, 'voimaan' => 5, 'kuun' => 3, 'loppuun' => 4, 'mennessä' => 5,
			'koskee' => 4, 'pienempiä' => 5, 'yrityksiä' => 5, 'ja' => 1, 'on' => 1, 'ei' => 2, 'se' => 1,
		],
		'cs' => [
			'jako' => 3, 'jsou' => 3, 'nebo' => 3, 'také' => 4, 'podle' => 4, 'mezi' => 3, 'který' => 4,
			'která' => 4, 'které' => 3, 'ještě' => 4, 'vláda' => 4, 'dnes' => 3, 'schválila' => 5, 'nový' => 3,
			'zákon' => 4, 'mění' => 4, 'pravidla' => 4, 'práce' => 3, 'ministra' => 3, 'opatření' => 5,
			'vstoupí' => 5, 'platnost' => 4, 'konce' => 3, 'měsíce' => 4, 'týká' => 4, 'menších' => 4,
			'firem' => 4, 'že' => 3, 'pro' => 2, 'ale' => 2, 'jeho' => 2, 'byl' => 2, 'byla' => 2, 'se' => 1,
			'na' => 1, 'je' => 1, 'do' => 1, 'to' => 1,
		],
		'sk' => [
			'ako' => 3, 'alebo' => 4, 'tiež' => 4, 'podľa' => 5, 'medzi' => 2, 'ktorý' => 4, 'ktorá' => 4,
			'ktoré' => 3, 'ešte' => 4, 'vláda' => 3, 'dnes' => 2, 'schválila' => 4, 'nový' => 2, 'zákon' => 3,
			'mení' => 5, 'pravidlá' => 5, 'práce' => 2, 'ministra' => 2, 'opatrenie' => 5, 'nadobudne' => 5,
			'platnosť' => 5, 'konca' => 3, 'mesiaca' => 4, 'týka' => 3, 'menších' => 3, 'firiem' => 5,
			'že' => 2, 'pre' => 2, 'ale' => 1, 'jeho' => 1, 'bol' => 1, 'bola' => 1, 'sa' => 2, 'na' => 1,
			'je' => 1, 'do' => 1, 'to' => 1,
		],
		'hu' => [
			'hogy' => 4, 'nem' => 2, 'egy' => 2, 'mint' => 2, 'csak' => 3, 'vagy' => 3, 'mert' => 4, 'után' => 3,
			'előtt' => 4, 'között' => 4, 'szerint' => 5, 'által' => 4, 'kormány' => 4, 'ma' => 2, 'új' => 2,
			'törvényt' => 5, 'fogadott' => 4, 'megváltoztatja' => 5, 'munka' => 3, 'szabályait' => 5,
			'miniszter' => 3, 'intézkedés' => 5, 'hónap' => 3, 'vége' => 3, 'lép' => 2, 'érvénybe' => 5,
			'kisebb' => 4, 'vállalatokat' => 5, 'érinti' => 4, 'és' => 2, 'van' => 2, 'is' => 1, 'az' => 2,
			'a' => 1,
		],
		'hr' => [
			'koji' => 3, 'koja' => 3, 'koje' => 3, 'nije' => 4, 'vlada' => 3, 'danas' => 4, 'novi' => 2,
			'zakon' => 3, 'mijenja' => 5, 'pravila' => 4, 'rada' => 2, 'prema' => 3, 'ministru' => 3,
			'mjera' => 4, 'stupa' => 4, 'snagu' => 4, 'kraja' => 3, 'mjeseca' => 5, 'manja' => 3,
			'poduzeća' => 5, 'također' => 5, 'je' => 1, 'se' => 1, 'na' => 1, 'da' => 2, 'za' => 1, 'su' => 2,
			'kao' => 2, 'ali' => 2, 'od' => 1, 'iz' => 2,
		],
		'ca' => [
			'amb' => 4, 'els' => 4, 'les' => 2, 'també' => 4, 'aquest' => 4, 'aquesta' => 4, 'govern' => 3,
			'avui' => 4, 'nou' => 2, 'decret' => 2, 'canvia' => 3, 'normes' => 4, 'feina' => 4, 'segons' => 4,
			'ministre' => 3, 'mesura' => 3, 'entrarà' => 4, 'vigor' => 2, 'abans' => 3, 'final' => 2, 'mes' => 2,
			'afectarà' => 5, 'empreses' => 4, 'petites' => 2, 'és' => 4, 'per' => 1, 'que' => 1, 'una' => 1,
			'del' => 1, 'el' => 1, 'la' => 1, 'un' => 1, 'i' => 1, 'en' => 1, 'de' => 1,
		],
		'tr' => [
			'için' => 4, 'olan' => 3, 'olarak' => 4, 'daha' => 3, 'çok' => 3, 'gibi' => 4, 'ama' => 3, 'veya' => 4,
			'sonra' => 3, 'önce' => 3, 'kadar' => 3, 'diye' => 4, 'değil' => 5, 'hükümet' => 5, 'bugün' => 4,
			'yeni' => 3, 'yasayı' => 5, 'onayladı' => 5, 'çalışma' => 4, 'kurallarını' => 5, 'değiştiriyor' => 5,
			'bakana' => 4, 'göre' => 3, 'önlem' => 4, 'ayın' => 4, 'sonundan' => 5, 'yürürlüğe' => 5,
			'girecek' => 4, 'küçük' => 3, 'şirketleri' => 5, 'etkileyecek' => 5, 've' => 1, 'bir' => 2,
			'bu' => 1, 'da' => 1, 'ile' => 2,
		],
	];

	/**
	 * @return array<string, string>
	 */
	public static function catalog(): array {
		return self::CATALOG;
	}

	public static function labelName(string $code): string {
		return self::CATALOG[$code] ?? strtoupper($code);
	}

	/**
	 * @return non-empty-string|null codice ISO 639-1, oppure null se il testo è troppo ambiguo
	 */
	public static function detect(string $text): ?string {
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$text = preg_replace('/https?:\/\/\S+/u', ' ', $text) ?? $text;
		$text = str_replace(["'", '’', '`'], ' ', $text);
		$text = preg_replace('/\s+/u', ' ', $text) ?? $text;
		$text = trim($text);
		if ($text === '') {
			return null;
		}

		$byScript = self::detectByScript($text);
		if ($byScript !== null) {
			return $byScript;
		}

		$lower = mb_strtolower($text, 'UTF-8');
		if (preg_match_all('/\p{L}+/u', $lower, $matches) === false || $matches[0] === []) {
			return null;
		}
		$tokens = $matches[0];
		if (count($tokens) < 8) {
			return null;
		}

		$scores = [];
		$seen = [];
		foreach (self::WORDS as $code => $words) {
			$scores[$code] = 0;
		}
		foreach ($tokens as $token) {
			foreach (self::WORDS as $code => $words) {
				if (!isset($words[$token])) {
					continue;
				}
				$seen[$code][$token] = ($seen[$code][$token] ?? 0) + 1;
				if ($seen[$code][$token] <= 4) {
					$scores[$code] += $words[$token];
				}
			}
		}

		arsort($scores);
		$bestCode = array_key_first($scores);
		if (!is_string($bestCode)) {
			return null;
		}
		$best = $scores[$bestCode];
		$second = 0;
		$seenBest = false;
		foreach ($scores as $score) {
			if (!$seenBest) {
				$seenBest = true;
				continue;
			}
			$second = $score;
			break;
		}

		if ($best < 8) {
			return null;
		}
		if ($second > 0 && $best < $second + 4 && $best < $second * 1.35) {
			return null;
		}

		return $bestCode;
	}

	private static function detectByScript(string $text): ?string {
		// Intervalli espliciti: in PHP \p{Katakana} (e simili) conta anche
		// la punteggiatura ideografica, e un testo cinese finiva classificato come giapponese.
		$hangul = self::countMatches($text, '/[\x{1100}-\x{11FF}\x{AC00}-\x{D7AF}]/u');
		$hira = self::countMatches($text, '/[\x{3040}-\x{309F}]/u');
		$kata = self::countMatches($text, '/[\x{30A0}-\x{30FF}]/u');
		$han = self::countMatches($text, '/[\x{4E00}-\x{9FFF}]/u');
		$hebrew = self::countMatches($text, '/[\x{0590}-\x{05FF}]/u');
		$arabic = self::countMatches($text, '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u');
		$greek = self::countMatches($text, '/[\x{0370}-\x{03FF}]/u');
		$cyrillic = self::countMatches($text, '/[\x{0400}-\x{04FF}\x{0500}-\x{052F}]/u');
		$letters = self::countMatches($text, '/\p{L}/u');
		if ($letters < 12) {
			return null;
		}

		$kana = $hira + $kata;
		if ($hangul >= 8 && $hangul * 2 >= $letters) {
			return 'ko';
		}
		if ($kana >= 4) {
			return 'ja';
		}
		if ($han >= 8 && $kana === 0 && $han * 2 >= $letters) {
			return 'zh';
		}
		if ($hebrew >= 8 && $hebrew * 2 >= $letters) {
			return 'he';
		}
		if ($arabic >= 8 && $arabic * 2 >= $letters) {
			$persian = self::countMatches($text, '/[پچژگ]/u');
			return $persian >= 1 ? 'fa' : 'ar';
		}
		if ($greek >= 8 && $greek * 2 >= $letters) {
			return 'el';
		}
		if ($cyrillic >= 12 && $cyrillic * 2 >= $letters) {
			return self::detectCyrillic($text);
		}

		return null;
	}

	private static function detectCyrillic(string $text): string {
		$scores = [
			'uk' => self::countMatches($text, '/[іїєґІЇЄҐ]/u') * 4,
			'bg' => self::countMatches($text, '/[ъЪѝЍ]/u') * 4,
			'sr' => self::countMatches($text, '/[ђјљњћџЂЈЉЊЋЏ]/u') * 4,
			'ru' => self::countMatches($text, '/[ыэёъЫЭЁЪ]/u') * 3,
		];
		arsort($scores);
		$best = array_key_first($scores);
		if (!is_string($best) || $scores[$best] < 4) {
			return 'ru';
		}
		return $best;
	}

	private static function countMatches(string $text, string $pattern): int {
		$count = preg_match_all($pattern, $text);
		return $count === false ? 0 : $count;
	}
}
