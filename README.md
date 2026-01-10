# 🚀 Maravel Framework

**Maravel Framework** è un framework PHP MVC leggero, modulare e orientato al mondo reale, progettato per lo sviluppo di applicazioni web, portali e gestionali **scalabili e manutenibili nel tempo**.

È pensato per chi vuole **controllo totale del codice**, senza rinunciare a una struttura moderna, estendibile e pronta per la produzione.

> ⚠️ Maravel **non è Laravel**.  
> È un framework indipendente, ispirato a concetti moderni, ma costruito per esigenze pratiche e progetti reali.

---

## ✨ Caratteristiche principali

- ✅ Architettura **MVC reale**
- ✅ Separazione netta **Core / App**
- ✅ Sistema **Plugin first-class**
- ✅ **Event Manager** integrato
- ✅ Supporto **Twig**
- ✅ Router custom semplice e leggibile
- ✅ Autenticazione centralizzata
- ✅ Flash messages
- ✅ CLI dedicata
- ✅ Sistema di **update centralizzati del core**
- ✅ PHP **8.3 / 8.4 ready**
- ✅ Pensato per progetti multipli e distribuzione controllata

---

## 📁 Struttura del progetto

```text
/
├── App/                # Logica applicativa (Controller, Models, Plugins, Services)
├── Core/               # Core del framework (Router, Auth, View, Event, ecc.)
├── ConfigFiles/        # Configurazioni
├── MyFiles/            # Files distribuiti / sincronizzati
├── migrations/         # Migrazioni PHP
├── MigrationsSQL/      # Migrazioni SQL
├── logs/               # Log applicativi
├── template/           # Template base
├── vendor/             # Dipendenze Composer
├── index.php           # Entry point web
├── cli.php             # Entry point CLI
├── install.php         # Script di installazione
├── composer.json
```
---

## 🔌 Sistema Plugin

Maravel include un **Plugin System avanzato**, ispirato ai migliori CMS ma adattato a un framework MVC.

Un plugin può:
- Registrare rotte
- Aggiungere voci di menu
- Agganciarsi a eventi
- Fornire controller, modelli e viste
- Estendere l’app senza modificare il Core

👉 Questo permette di sviluppare funzionalità **modulari e disattivabili**.

---

## 🔔 Event Manager

Il framework integra un **Event Manager** che consente di:

- Disaccoppiare la logica
- Reagire ad azioni di dominio
- Estendere il comportamento senza modificare il codice esistente

Esempi:
- `profilo.completato`
- `annuncio.creato`
- `utente.registrato`

---

## 🔁 Update centralizzati del Core (Feature chiave)

Maravel Framework include un **sistema di aggiornamento centralizzato del core**, pensato per gestire **più progetti basati sullo stesso framework**.

Con un solo comando è possibile:

- Scansionare l’intero Core
- Generare una rappresentazione strutturata (`core.json`)
- Aggiornare i file condivisi
- Distribuire automaticamente il core aggiornato a tutti i progetti figli

### Comando

```bash
php build-core-json.php

✅ Framework core scansionato
✅ MyFiles aggiornato
✅ core.json generato

Vantaggi
	•	🔁 Aggiorni il core una sola volta
	•	🧩 Nessun impatto su App/ o plugin
	•	🚀 Evoluzione continua del framework
	•	🛡️ Riduzione errori e divergenze tra progetti
	•	🏢 Ideale per SaaS, portali multipli, agenzie

🧪 CLI

Maravel include un entry point CLI (cli.php) per:
	•	Migrazioni
	•	Operazioni di manutenzione
	•	Script interni
	•	Task automatizzati

Espandibile nel tempo con comandi custom.

🔐 Sicurezza
	•	Protezione da accessi diretti
	•	Auth centralizzato
	•	Session handling
	•	Separazione aree pubbliche / private

(Estendibile con CSRF, rate limiting, middleware, ecc.)

⚙️ Requisiti
	•	PHP ≥ 8.3
	•	Estensioni PHP comuni (PDO, JSON, mbstring)
	•	Composer
	•	Database MySQL / MariaDB

🛣️ Roadmap (in evoluzione)
	•	Middleware system
	•	Response object
	•	Validation layer
	•	Dependency Injection Container
	•	Versioning del core
	•	Update differenziali e rollback
	•	API / JSON mode

🧠 Filosofia

Maravel nasce da progetti reali, non da tutorial.

È pensato per:
	•	Portali custom
	•	SaaS verticali
	•	Gestionali
	•	Progetti multi-cliente
	•	Chi vuole controllo totale senza over-engineering


👤 Autore

Marco Dattisi
Ingegnere informatico / Web developer

