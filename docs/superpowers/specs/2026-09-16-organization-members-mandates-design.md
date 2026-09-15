# Organizations — Membri, cariche e mandati

Data: 2026-09-16
Stato: design approvato in chat, in attesa di revisione della specifica scritta

## Obiettivo

Aggiungere alla scheda di un'organizzazione una tab **Membri** dedicata alle persone che ricoprono cariche nell'organizzazione (Presidente, Vicepresidente, Segretario, Tesoriere, Consigliere, ecc.), mantenendo lo storico completo dei mandati e integrandosi con **People** tramite il suo provider pubblico, senza query dirette alle tabelle People.

Contestualmente, spostare i campi tecnici dell'organizzazione in una tab **Sistema**, coerente con People: UUID, data creazione e ultima modifica.

## Principi di dominio

1. Organizations possiede gli **incarichi organizzativi**; People possiede l'anagrafica della persona.
2. Un incarico è una relazione storicizzata tra organizzazione, persona, carica e intervallo temporale.
3. La stessa persona può avere contemporaneamente più cariche nella stessa organizzazione.
4. Lo storico non viene sovrascritto quando un incarico termina prima del previsto.
5. Le dimissioni, la revoca, la decadenza e la fine mandato sono eventi di cessazione distinti.
6. La durata è flessibile: l'interfaccia offre scorciatoie 1–5 anni e una modalità personalizzata, ma il dato canonico è costituito dalle date.
7. Il collegamento a People usa UUID/provider pubblico; nessuna foreign key o query diretta verso `#__xdecaropeople_*`.
8. Non si usa il concetto di "membership" generale, per evitare sovrapposizione con il componente Membership. Internamente l'entità è un **appointment/incarico**.

## Navigazione della scheda organizzazione

Ordine previsto delle tab:

1. Identità
2. Contatti
3. Membri
4. Pubblicazione
5. Sistema

### Sistema

Spostare `uuid` da Identità a Sistema. Aggiungere in sola lettura:

- UUID
- Creato
- Ultima modifica

Per una nuova organizzazione non ancora salvata, la tab Membri è visibile ma mostra un messaggio che richiede il primo salvataggio prima di aggiungere incarichi.

## Modello dati

Nuova tabella:

`#__xdecaroorganizations_appointments`

Campi proposti:

- `id` INT UNSIGNED PK AUTO_INCREMENT
- `uuid` CHAR(36) NOT NULL UNIQUE
- `organization_id` INT UNSIGNED NOT NULL
- `person_uuid` CHAR(36) NOT NULL
- `person_name_snapshot` VARCHAR(255) NOT NULL
- `role_code` VARCHAR(50) NOT NULL
- `role_custom` VARCHAR(190) NULL
- `starts_on` DATE NOT NULL
- `planned_ends_on` DATE NULL
- `ended_on` DATE NULL
- `end_reason` VARCHAR(50) NULL
- `end_note` TEXT NULL
- `notes` TEXT NULL
- `state` TINYINT NOT NULL DEFAULT 1
- `created` DATETIME NOT NULL
- `created_by` INT UNSIGNED NOT NULL DEFAULT 0
- `modified` DATETIME NULL
- `modified_by` INT UNSIGNED NOT NULL DEFAULT 0

Indici:

- UNIQUE `idx_appointment_uuid` (`uuid`)
- INDEX `idx_appointment_org` (`organization_id`)
- INDEX `idx_appointment_person` (`person_uuid`)
- INDEX `idx_appointment_org_dates` (`organization_id`, `starts_on`, `planned_ends_on`)
- INDEX `idx_appointment_state` (`state`)

Foreign key locale:

- `organization_id` → `#__xdecaroorganizations_organizations.id` con `ON DELETE CASCADE`.

Non deve esistere alcuna foreign key verso People.

## Cariche

Catalogo iniziale disponibile nell'interfaccia:

- Presidente (`president`)
- Vicepresidente (`vice_president`)
- Segretario (`secretary`)
- Tesoriere (`treasurer`)
- Consigliere (`councillor`)
- Revisore (`auditor`)
- Direttore (`director`)
- Coordinatore (`coordinator`)
- Altro (`custom`)

Se `role_code = custom`, `role_custom` è obbligatorio.

Le etichette devono essere traducibili tramite file lingua. Il catalogo resta volutamente semplice nella prima versione; non viene introdotta una tabella di configurazione delle cariche finché non emerge un'esigenza reale.

## Durata del mandato

L'UI offre:

- 1 anno
- 2 anni
- 3 anni
- 4 anni
- 5 anni
- Personalizzata

Per 1–5 anni, `planned_ends_on` viene calcolata automaticamente dalla data di inizio. L'utente può comunque correggerla se necessario.

Con Personalizzata, l'utente imposta direttamente `planned_ends_on`; questo copre durate come 18 mesi, 6 anni, 7 anni o date non coincidenti con un anniversario esatto.

Non è necessario memorizzare il numero di anni come dato canonico: le date sono la fonte di verità.

## Cessazione e stato visuale

Valori di `end_reason`:

- `term_end` — fine mandato
- `resignation` — dimissioni
- `revocation` — revoca
- `forfeiture` — decadenza
- `other` — altro

`ended_on` è la data effettiva di cessazione e non sostituisce `planned_ends_on`.

Lo stato visuale è derivato, non duplicato in un altro campo di dominio:

- **In carica**: nessuna cessazione e data prevista non trascorsa (o assente)
- **Scaduto**: nessuna cessazione registrata e `planned_ends_on` precedente alla data odierna
- **Terminato**: `end_reason = term_end`
- **Dimesso**: `end_reason = resignation`
- **Revocato**: `end_reason = revocation`
- **Decaduto**: `end_reason = forfeiture`
- **Terminato** con dettaglio "Altro" per `end_reason = other`

`state` rimane esclusivamente lo stato Joomla del record (pubblicato/non pubblicato/cestinato), non deve essere usato per rappresentare il ciclo di vita del mandato.

## Tab Membri — UX

La tab è divisa in due sezioni.

### In carica

Tabella compatta con:

- Persona
- Carica
- Mandato (inizio → fine prevista)
- Stato
- Azioni

Ordinamento consigliato: carica, poi nome persona.

Azioni disponibili:

- Modifica
- Termina mandato
- Dimissioni
- Revoca
- Decadenza

### Storico

Mostra gli incarichi cessati e quelli scaduti, con:

- Persona
- Carica
- Mandato previsto
- Data cessazione effettiva
- Esito/stato
- Azioni di consultazione/modifica amministrativa

Lo storico non deve essere cancellato quando una persona cambia carica o viene sostituita.

## Aggiunta/modifica incarico

La tab usa una modale dedicata, non un subform enorme dentro il form principale dell'organizzazione.

Campi:

- Persona *
- Carica *
- Carica personalizzata (solo se Altro)
- Data inizio *
- Durata prevista (1–5 anni / Personalizzata)
- Data fine prevista
- Note

La scelta della persona usa una ricerca incrementale verso People e restituisce il riferimento UUID più il nome visualizzato.

Al salvataggio viene registrato anche `person_name_snapshot` per mantenere leggibile lo storico anche se in seguito il record People viene archiviato o il nome visualizzato cambia.

## Dimissioni e altre cessazioni

Le azioni di cessazione aprono una modale ridotta con:

- Persona (sola lettura)
- Carica (sola lettura)
- Tipo cessazione
- Data cessazione *
- Nota/motivo

La cessazione aggiorna `ended_on`, `end_reason` ed `end_note`; non altera le date previste originali.

## Integrazione con People

Organizations deve risolvere e cercare persone tramite il contratto pubblico di People, usando `PersonProviderService`/componente bootstrappato da Joomla.

Operazioni necessarie:

- ricerca persona per testo (`searchPeople`)
- risoluzione per UUID (`getPerson` / `getPeopleByUuids`)

Organizations non deve:

- importare direttamente model/table di People;
- interrogare `#__xdecaropeople_people`;
- creare foreign key verso tabelle People.

Se People non è installato/disponibile, la tab Membri rimane leggibile tramite snapshot per gli incarichi già esistenti, mentre l'aggiunta di nuove persone viene disabilitata con messaggio chiaro.

## Architettura Joomla proposta

La gestione degli incarichi deve restare separata dal salvataggio principale dell'organizzazione.

Componenti previsti:

- `OrganizationAppointmentTable`
- `OrganizationAppointmentModel` / servizio dominio equivalente
- `OrganizationAppointmentsModel` per lettura elenco
- controller/task dedicati per create/update/end
- endpoint AJAX JSON protetti da token CSRF per ricerca People e operazioni della modale
- rendering della tab Membri nel form edit organizzazione tramite layout/subview dedicato

Questa separazione evita che un errore in una riga membro impedisca il salvataggio dell'intera anagrafica organizzazione e consente di aggiornare un incarico senza risalvare tutti gli altri campi.

## Validazioni

Obbligatorie:

- organizzazione esistente
- `person_uuid` UUID valido
- persona risolvibile tramite People per nuovi incarichi
- `starts_on` valido
- `planned_ends_on >= starts_on`, se presente
- `ended_on >= starts_on`, se presente
- `role_code` nel catalogo ammesso
- `role_custom` obbligatorio solo per `custom`
- `end_reason` ammesso se `ended_on` è valorizzato
- cessazione non precedente all'inizio

La stessa persona può avere più incarichi sovrapposti nella stessa organizzazione. Non viene imposto un vincolo UNIQUE su organizzazione/persona/carica.

Per possibili duplicati identici o sovrapposti della stessa persona e stessa carica, la prima versione può mostrare un avviso senza bloccare il salvataggio; non deve introdurre un vincolo rigido che impedisca casi reali eccezionali.

## Permessi

Riutilizzare `core.manage`, `core.create`, `core.edit`, `core.edit.state` e `core.delete` dove appropriato sul componente Organizations.

Le operazioni di cessazione richiedono almeno permesso di modifica dell'organizzazione/incarico. Il cestino di un incarico richiede permesso adeguato; nessuna cancellazione permanente dalla UI standard.

L'accesso ai dati People avviene con il provider pubblico e le sue autorizzazioni. La tab Membri non richiede dati sensibili di People: nome/UUID sono sufficienti.

## Sicurezza e integrità

- CSRF token su tutte le mutazioni AJAX.
- Validazione server-side di tutte le date, UUID e valori enum-like.
- Non fidarsi del nome persona inviato dal browser: per un nuovo incarico il server risolve nuovamente la persona via provider e genera lo snapshot.
- Nessuna query cross-component diretta.
- Nessuna cancellazione automatica dello storico al termine del mandato.

## Migrazione

La release che implementerà il design deve:

1. creare `#__xdecaroorganizations_appointments` nello script di installazione;
2. aggiungere una migration SQL incrementale per la nuova tabella;
3. spostare solo la presentazione di UUID nella tab Sistema, senza migrazione dati dell'organizzazione;
4. mantenere compatibilità con le organizzazioni già esistenti.

## Test richiesti

TDD prima dell'implementazione.

Contratti/unit test minimi:

- schema appointments e indici;
- tab Sistema contiene UUID/created/modified e Identità non contiene UUID;
- tab Membri presente nel form edit;
- durata 1–5 anni calcola la fine prevista correttamente;
- durata personalizzata conserva la data fornita;
- stessa persona può avere più cariche contemporanee;
- cessazione per dimissioni conserva la fine prevista e registra la fine effettiva;
- mapping stato visuale In carica/Scaduto/Terminato/Dimesso/Revocato/Decaduto;
- nessuna query diretta a `#__xdecaropeople_*` in Organizations;
- fallback snapshot quando People non è disponibile;
- Joomla runtime 6.1.x e pipeline esistente completamente verdi.

## Non obiettivi della prima versione

- gestione generale soci/tesserati dell'organizzazione;
- quote associative;
- elezioni/votazioni;
- gestione configurabile del catalogo cariche da backend;
- documenti di nomina/dimissione allegati direttamente a Organizations;
- sincronizzazione inversa che scrive dati dentro People.

Un futuro collegamento con Documents potrà associare delibere, verbali o lettere di dimissioni tramite riferimenti pubblici, senza cambiare il modello base dell'incarico.

## Criteri di accettazione

Il lavoro è completo quando un amministratore può:

1. aprire un'organizzazione già salvata;
2. cercare una persona proveniente da People;
3. assegnarle una o più cariche anche contemporanee;
4. scegliere una durata rapida 1–5 anni oppure una data personalizzata;
5. vedere gli incarichi attivi separati dallo storico;
6. registrare fine mandato, dimissioni, revoca o decadenza senza perdere le date previste;
7. consultare lo storico anche se la persona viene successivamente archiviata in People;
8. vedere UUID/creazione/modifica nella tab Sistema anziché Identità;
9. usare il tutto senza query dirette o vincoli DB verso People.
