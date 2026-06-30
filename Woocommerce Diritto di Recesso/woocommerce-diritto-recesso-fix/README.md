# WooCommerce Diritto di Recesso

Plugin WordPress per la gestione del **diritto di recesso** secondo la normativa italiana (**art. 54-bis del Codice del Consumo – D.Lgs. 206/2005**, in attuazione della Direttiva 2011/83/UE) per i negozi che utilizzano **WooCommerce**.

Il plugin consente ai clienti di inviare una richiesta di recesso tramite un modulo frontend, genera automaticamente il **modulo di recesso in PDF** conforme alla normativa, e fornisce all'amministratore un'area dedicata per gestire tutte le richieste con notifiche email automatiche.

---

## Caratteristiche principali

- ✅ **Modulo frontend** inseribile in qualsiasi pagina tramite lo shortcode `[modulo_recesso]`
- ✅ **Validazione automatica**: verifica che l'ordine esista, che l'email corrisponda e che la richiesta sia entro i **14 giorni**
- ✅ **Generazione PDF** del modulo di recesso conforme all'art. 54-bis (senza librerie esterne)
- ✅ **Area amministrativa** con elenco richieste, filtri per stato, ricerca, dettaglio e paginazione
- ✅ **Gestione degli stati**: In attesa, Approvata, Rifiutata, Completata
- ✅ **Notifiche email automatiche** al cliente e all'amministratore
- ✅ **Tabella database dedicata** con gestione pulita di installazione e disinstallazione
- ✅ **Interamente in italiano**: codice, commenti, interfaccia e documentazione

---

## Requisiti

| Componente   | Versione minima |
|--------------|-----------------|
| WordPress    | 5.8 o superiore |
| PHP          | 7.4 o superiore |
| WooCommerce  | 5.0 o superiore |

> ⚠️ Il plugin richiede **WooCommerce** attivo. In caso contrario, mostrerà un avviso nell'area amministrativa e non si attiverà.

---

## Installazione

### Metodo 1 – Caricamento da pannello WordPress

1. Comprimi la cartella `woocommerce-diritto-recesso` in un file `.zip`.
2. Accedi al pannello di amministrazione di WordPress.
3. Vai su **Plugin → Aggiungi nuovo → Carica plugin**.
4. Seleziona il file `.zip` e clicca su **Installa ora**.
5. Clicca su **Attiva**.

### Metodo 2 – Caricamento via FTP

1. Copia la cartella `woocommerce-diritto-recesso` nella directory:
   ```
   /wp-content/plugins/
   ```
2. Accedi al pannello di WordPress.
3. Vai su **Plugin** e clicca su **Attiva** sotto "WooCommerce Diritto di Recesso".

All'attivazione, il plugin crea automaticamente la tabella `wp_wcdr_richieste_recesso` nel database.

---

## Utilizzo

### 1. Inserire il modulo di recesso in una pagina

Crea (o modifica) una pagina — ad esempio "Diritto di Recesso" — e inserisci lo shortcode:

```
[modulo_recesso]
```

Il modulo mostrerà i seguenti campi:

- **Numero ordine** (obbligatorio)
- **Nome e cognome** (obbligatorio)
- **Email** (obbligatorio — deve corrispondere a quella dell'ordine)
- **Data di acquisto** (obbligatorio)
- **Motivo del recesso** (facoltativo, come previsto dalla normativa)
- Casella di consenso al trattamento dei dati

### 2. Cosa succede dopo l'invio

Quando un cliente invia il modulo, il plugin:

1. Verifica che tutti i campi obbligatori siano compilati.
2. Controlla che l'**ordine esista** in WooCommerce.
3. Verifica che l'**email corrisponda** a quella dell'ordine.
4. Controlla che la richiesta sia entro i **14 giorni** (calcolati dalla data di completamento dell'ordine, o in mancanza dalla data di creazione).
5. Salva la richiesta nel database con stato **"In attesa"**.
6. Genera il **PDF** del modulo di recesso.
7. Invia un'email di conferma al **cliente** e una notifica all'**amministratore**.
8. Mostra al cliente un messaggio di conferma.

### 3. Gestione delle richieste (area amministrativa)

Nel menu di WordPress troverai la voce **"Diritto Recesso"** (con un badge che indica le richieste in attesa).

Da qui puoi:

- **Visualizzare l'elenco** di tutte le richieste con filtri per stato e ricerca per ordine/nome/email.
- **Aprire il dettaglio** di ogni richiesta, inclusi i dati dell'ordine WooCommerce collegato.
- **Cambiare lo stato** della richiesta (In attesa → Approvata / Rifiutata / Completata). Ad ogni cambio di stato il cliente riceve un'email automatica, comprese le eventuali note inserite dall'amministratore.
- **Scaricare il PDF** del modulo di recesso.

---

## Stati delle richieste

| Stato        | Descrizione                                              |
|--------------|----------------------------------------------------------|
| In attesa    | Richiesta ricevuta, in attesa di valutazione             |
| Approvata    | Richiesta accettata dall'esercente                       |
| Rifiutata    | Richiesta non accolta (es. fuori termine o non valida)   |
| Completata   | Procedura di recesso conclusa                            |

> ℹ️ Il plugin gestisce esclusivamente la **richiesta di recesso**. La gestione del rimborso non è inclusa e va effettuata separatamente (ad esempio direttamente da WooCommerce).

---

## Notifiche email

| Evento                                  | Destinatario   |
|-----------------------------------------|----------------|
| Nuova richiesta inviata                 | Cliente        |
| Nuova richiesta inviata                 | Amministratore |
| Cambio di stato della richiesta         | Cliente        |

Le email vengono inviate tramite la funzione standard `wp_mail()` di WordPress, in formato HTML. L'indirizzo del mittente corrisponde a quello configurato in **Impostazioni → Generali** di WordPress. La notifica di nuova richiesta viene inviata sempre a **info@viridiasrl.it** (è possibile sovrascrivere questo indirizzo definendo la costante `WCDR_ADMIN_EMAIL` nel file `wp-config.php`).

---

## Struttura del plugin

```
woocommerce-diritto-recesso/
├── woocommerce-diritto-recesso.php   # File principale (header, costanti, hook)
├── uninstall.php                     # Pulizia dati alla disinstallazione
├── README.md                         # Questa documentazione
├── includes/                         # Logica principale
│   ├── class-wcdr-plugin.php         # Classe principale (Singleton)
│   ├── class-wcdr-database.php       # Gestione tabella e query
│   ├── class-wcdr-pdf.php            # Generatore PDF del modulo
│   ├── class-wcdr-emails.php         # Notifiche email
│   └── class-wcdr-shortcode.php      # Shortcode e gestione form frontend
├── admin/                            # Area amministrativa
│   ├── class-wcdr-admin.php          # Menu, lista, dettaglio, azioni
│   ├── css/wcdr-admin.css            # Stili admin
│   └── views/                        # Template delle viste
│       ├── vista-lista.php
│       └── vista-dettaglio.php
├── public/                           # Risorse frontend
│   ├── css/wcdr-frontend.css
│   └── js/wcdr-frontend.js
└── languages/                        # Cartella per i file di traduzione
```

---

## Database

Il plugin crea una tabella dedicata: `{prefisso}_wcdr_richieste_recesso`

| Colonna          | Tipo            | Descrizione                              |
|------------------|-----------------|------------------------------------------|
| `id`             | BIGINT          | Identificativo univoco                   |
| `numero_ordine`  | VARCHAR(50)     | Numero dell'ordine                       |
| `nome_cliente`   | VARCHAR(255)    | Nome e cognome del cliente               |
| `email_cliente`  | VARCHAR(255)    | Email del cliente                        |
| `motivo`         | TEXT            | Motivo del recesso (facoltativo)         |
| `data_acquisto`  | DATE            | Data di acquisto indicata dal cliente    |
| `data_richiesta` | DATETIME        | Data e ora di invio della richiesta      |
| `stato`          | VARCHAR(20)     | Stato della richiesta                    |
| `note_admin`     | TEXT            | Note dell'amministratore                 |
| `file_pdf`       | VARCHAR(255)    | Percorso del PDF generato                |

### Disinstallazione

Quando il plugin viene **eliminato** (non semplicemente disattivato), il file `uninstall.php`:

- elimina la tabella custom dal database;
- rimuove le opzioni salvate dal plugin;
- elimina la cartella `wp-content/uploads/wcdr-moduli-recesso/` con tutti i PDF generati.

> La semplice **disattivazione** non rimuove alcun dato, per evitare perdite accidentali.

---

## Sicurezza

- Tutti i form sono protetti tramite **nonce** di WordPress (protezione CSRF).
- I dati in input vengono **sanitizzati** e quelli in output **escaped**.
- Le query al database utilizzano `$wpdb->prepare()` con whitelist delle colonne ordinabili.
- I PDF sono salvati in una cartella protetta da `.htaccess` (accesso diretto negato) e scaricabili solo dagli amministratori tramite link firmato con nonce.
- L'accesso alle pagine admin richiede la capability `manage_woocommerce`.

---

## Note legali

Questo plugin è uno strumento di supporto operativo e **non costituisce consulenza legale**. È responsabilità dell'esercente verificare la conformità del modulo e delle procedure alla normativa vigente e adattare i testi alle proprie esigenze.

---

## Licenza

Distribuito con licenza **GPL v2 o successiva**, in conformità con le linee guida dei plugin WordPress.

---

## Changelog

### 1.0.0
- Prima versione del plugin.
- Modulo frontend con shortcode `[modulo_recesso]`.
- Validazione ordine ed entro i 14 giorni.
- Generazione PDF del modulo di recesso.
- Area amministrativa con elenco, dettaglio e gestione stati.
- Notifiche email automatiche.
