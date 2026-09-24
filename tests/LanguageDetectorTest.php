<?php

declare(strict_types=1);

require dirname(__DIR__) . '/extensions/xExtension-LanguageCatalog/LanguageDetector.php';

$cases = [
	'it' => 'Il governo ha approvato oggi un nuovo decreto che modifica le regole per il lavoro. Secondo il ministro, la misura entrerà in vigore entro la fine del mese e riguarderà anche le imprese più piccole.',
	'en' => 'The government approved a new bill today that changes the rules for workers. According to the minister, the measure will take effect before the end of the month and will also affect smaller companies.',
	'fr' => "Le gouvernement a approuvé aujourd'hui un nouveau projet qui modifie les règles du travail. Selon le ministre, la mesure entrera en vigueur avant la fin du mois et concernera aussi les petites entreprises.",
	'de' => 'Die Regierung hat heute ein neues Gesetz beschlossen, das die Regeln für die Arbeit ändert. Nach Angaben des Ministers tritt die Maßnahme noch vor Ende des Monats in Kraft und betrifft auch kleinere Unternehmen.',
	'es' => 'El gobierno aprobó hoy un nuevo decreto que cambia las reglas del trabajo. Según el ministro, la medida entrará en vigor antes de que termine el mes y también afectará a las empresas más pequeñas.',
	'pt' => 'O governo aprovou hoje um novo decreto que muda as regras do trabalho. Segundo o ministro, a medida vai entrar em vigor antes do fim do mês e também vai afetar as empresas menores.',
	'nl' => 'De regering heeft vandaag een nieuwe wet goedgekeurd die de regels voor werk verandert. Volgens de minister treedt de maatregel voor het einde van de maand in werking en geldt hij ook voor kleinere bedrijven.',
	'pl' => 'Rząd przyjął dziś nową ustawę, która zmienia zasady pracy. Według ministra przepis wejdzie w życie przed końcem miesiąca i obejmie także mniejsze firmy.',
	'ru' => 'Правительство сегодня одобрило новый закон, который меняет правила для работников. По словам министра, мера вступит в силу до конца месяца и затронет также небольшие компании.',
	'uk' => 'Уряд сьогодні схвалив новий закон, який змінює правила для працівників. За словами міністра, захід набуде чинності до кінця місяця і також торкнеться невеликих компаній.',
	'zh' => '政府今天批准了一项新法案，将改变劳动者的规则。据部长表示，该措施将在月底前生效，也会影响较小的企业。',
	'ja' => '政府は本日、労働者の規則を変更する新しい法案を承認した。大臣によると、この措置は月末までに施行され、中小企業にも影響する。',
	'ar' => 'وافقت الحكومة اليوم على قانون جديد يغيّر قواعد العمل. وبحسب الوزير، سيدخل الإجراء حيز التنفيذ قبل نهاية الشهر وسيؤثر أيضاً على الشركات الصغيرة.',
	'el' => 'Η κυβέρνηση ενέκρινε σήμερα ένα νέο νομοσχέδιο που αλλάζει τους κανόνες εργασίας. Σύμφωνα με τον υπουργό, το μέτρο θα τεθεί σε ισχύ πριν από το τέλος του μήνα.',
	'he' => 'הממשלה אישרה היום חוק חדש שמשנה את כללי העבודה. לפי השר, הצעד ייכנס לתוקף לפני סוף החודש וגם ישפיע על חברות קטנות.',
	'ko' => '정부는 오늘 노동 규칙을 바꾸는 새로운 법안을 승인했다. 장관에 따르면 이 조치는 월말 전에 시행되며 소규모 기업에도 영향을 미친다.',
];

$failed = 0;
foreach ($cases as $expected => $text) {
	$got = LanguageDetector::detect($text);
	if ($got !== $expected) {
		fwrite(STDERR, "FAIL atteso {$expected}, ottenuto " . var_export($got, true) . "\n");
		$failed++;
		continue;
	}
	echo "OK {$expected}\n";
}

$short = LanguageDetector::detect('Ciao mondo');
if ($short !== null) {
	fwrite(STDERR, "FAIL un testo corto non deve ricevere una lingua\n");
	$failed++;
} else {
	echo "OK testo corto\n";
}

exit($failed === 0 ? 0 : 1);
