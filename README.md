# Notizie — FreshRSS su Contabo con Coolify

[FreshRSS](https://freshrss.org/) è un aggregatore di feed RSS e Atom da tenere sul proprio server. Questo progetto lo prepara per un VPS [Contabo](https://contabo.com/) gestito da [Coolify](https://coolify.io/): PostgreSQL, HTTPS tramite il proxy di Coolify, interfaccia in italiano e catalogo degli articoli per lingua.

FreshRSS è software libero, licenza AGPL-3.0. L'estensione in `extensions/xExtension-LanguageCatalog` è AGPL-3.0-or-later.

## Catalogo per lingua

FreshRSS non ha un campo «lingua dell'articolo». L'estensione **LanguageCatalog**, già inclusa nell'immagine, fa due cose a ogni aggiornamento dei feed:

1. Legge titolo e testo e stima la lingua (alfabeto e parole tipiche, senza servizi esterni).
2. Aggiunge un tag `lingua-it`, `lingua-en`, `lingua-fr` e così via. FreshRSS trasforma quel tag in un'etichetta visibile nel menu a sinistra, sotto **Le mie etichette**.

Da lì si apre solo l'italiano, solo l'inglese, o qualunque altra lingua riconosciuta. Un testo troppo corto o mescolato resta senza etichetta. L'etichetta si può correggere a mano dal fondo dell'articolo.

Le lingue previste sono italiano, inglese, francese, tedesco, spagnolo, portoghese, olandese, polacco, rumeno, svedese, danese, norvegese, finlandese, ceco, slovacco, ungherese, croato, catalano, greco, turco, russo, ucraino, bulgaro, serbo, arabo, persiano, ebraico, cinese, giapponese e coreano.

I feed restano nelle categorie in cui li metti tu. L'etichetta riguarda il singolo articolo, non la cartella del feed.

## Cosa gira sul server

| Servizio | Immagine | Ruolo |
| --- | --- | --- |
| `freshrss` | `freshrss/freshrss:1.30.0` più l'estensione | Sito e aggiornamento dei feed ogni quarto d'ora circa |
| `freshrss-db` | `postgres:17-alpine` | Database, non esposto su Internet |

I dati stanno in tre volumi Docker: `freshrss-data`, `freshrss-extensions` e `freshrss-postgresql`.

## Prima di Coolify, sul VPS Contabo

Serve un VPS con almeno 2 GB di RAM; 4 GB sono più comodi, perché sullo stesso server vivono Coolify, Traefik, FreshRSS e PostgreSQL.

1. Punta un record DNS `A` (per esempio `notizie.tuodominio.it`) all'indirizzo IPv4 del VPS.
2. Nel firewall di Contabo e sul server apri TCP `22`, `80`, `443` e `8000` (l'interfaccia di Coolify).
3. Installa Coolify con la procedura ufficiale, poi entra su `http://IP-DEL-VPS:8000` e collega il dominio di Coolify se vuoi l'HTTPS anche per il pannello.

## Deploy

Il modo previsto è un repository Git con questa cartella, collegato a Coolify come applicazione **Docker Compose**.

1. Crea un progetto Coolify e una risorsa Docker Compose che punta a questo repository. Il file è `docker-compose.yml` nella radice.
2. Prima di avviare, assegna a `freshrss` il dominio pubblico, ad esempio `https://notizie.tuodominio.it`. Coolify riempie così `SERVICE_URL_FRESHRSS_80`, che diventa l'indirizzo dell'installazione. Va impostato prima del primo deploy: FreshRSS lo registra solo in quel momento.
3. Imposta le variabili d'ambiente. Le password vanno scelte senza spazi, virgolette o il simbolo `$`.

| Variabile | Valore |
| --- | --- |
| `ADMIN_EMAIL` | la tua email |
| `ADMIN_PASSWORD` | password dell'utente `admin` |
| `ADMIN_API_PASSWORD` | password diversa, per le app mobili |
| `SERVICE_PASSWORD_DB` | lasciala generare da Coolify, oppure scegline una lunga |
| `TZ` | `Europe/Rome` se non vuoi il valore già previsto |
| `DB_BASE` | `freshrss` |
| `DB_USER` | `freshrss` |

4. Avvia il deploy e attendi che entrambi i servizi risultino healthy. Il primo avvio crea il database, l'utente `admin` e le etichette delle lingue.
5. Apri il dominio e accedi con l'utente `admin`.

In **Impostazioni → Estensioni** deve comparire LanguageCatalog, di tipo sistema. In **Amministrazione** controlla che l'indirizzo di base sia il dominio HTTPS, senza barra finale.

PostgreSQL non va pubblicato. Il sito esce solo dal proxy di Coolify.

## Dopo il primo accesso

- Aggiungi i feed da **Iscrizione** oppure importa un file OPML.
- Al primo aggiornamento, gli articoli nuovi compaiono sotto l'etichetta della lingua.
- L'API è attiva: app come Read You, Capy Reader, NetNewsWire o Fluent Reader si collegano con l'utente `admin` e `ADMIN_API_PASSWORD`. L'indirizzo API è quello mostrato in FreshRSS sotto **Impostazioni → Profilo → API**.
- I feed di partenza non vengono creati, così il catalogo contiene solo le fonti che aggiungi tu.

Se il dominio è stato assegnato dopo il primo avvio, l'indirizzo di base resta quello vecchio. Si corregge in **Amministrazione → Configurazione del sistema**, campo dell'indirizzo di base, poi si salva.

## Prova in locale

Docker Desktop o Docker Engine, dalla cartella del progetto:

```powershell
copy .env.example .env
docker compose --env-file .env -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Il sito risponde su `http://localhost:8080`. Il file `docker-compose.local.yml` non va usato su Coolify.

Il controllo del rilevatore di lingua, senza avviare FreshRSS:

```powershell
docker run --rm --entrypoint php -v "${PWD}:/src" -w /src freshrss/freshrss:1.30.0 tests/LanguageDetectorTest.php
```

## Backup e aggiornamenti

Da Coolify si pianifica il backup dei volumi `freshrss-postgresql` e `freshrss-data`. Il volume del database da solo non basta: in `freshrss-data` stanno gli utenti e la configurazione.

Per aggiornare FreshRSS si cambia il tag in `Dockerfile` (`FROM freshrss/freshrss:1.30.0`) e si rideploya. I volumi restano. Non cancellarli per applicare un cambio di password o di dominio: l'installazione automatica e la creazione dell'utente partono solo a volume vuoto.

## Limiti

Il riconoscimento sbaglia sui testi brevi, sulle citazioni in un'altra lingua e su croato, serbo e bosniaco scritti in alfabeto latino, che si somigliano. In quel caso l'articolo resta senza etichetta oppure va spostato a mano. Non è una traduzione e non chiama un modello di linguaggio.
