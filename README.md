# 🚀 Maravel Framework

```md
![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-active%20development-orange)
![GitHub release](https://img.shields.io/github/v/release/marchino321/maravel)
![GitHub issues](https://img.shields.io/github/issues/marchino321/maravel)
![GitHub last commit](https://img.shields.io/github/last-commit/marchino321/maravel)
```

**Maravel Framework** is a lightweight, modular PHP MVC framework focused on real-world needs, designed for building web applications, portals, and management systems that are **scalable and maintainable over time**.

It is built for developers who want **full control over their code**, without giving up a modern, extensible, and production-ready structure.

> ⚠️ Maravel **is not Laravel**.  
> It is an independent framework, inspired by modern concepts, but built for practical needs and real projects.

---

## ✨ Key Features

- ✅ **True MVC architecture**
- ✅ Clear **Core / App** separation
- ✅ **First-class Plugin system**
- ✅ Integrated **Event Manager**
- ✅ **Twig** support
- ✅ Simple and readable custom router
- ✅ Centralized authentication
- ✅ Flash messages
- ✅ Dedicated CLI
- ✅ **Centralized core update system**
- ✅ **PHP 8.3 / 8.4 ready**
- ✅ Designed for multi-project setups and controlled distribution

---

## 📁 Project Structure

```text
/
├── App/                # Application logic (Controllers, Models, Plugins, Services)
├── Core/               # Framework core (Router, Auth, View, Event, etc.)
├── ConfigFiles/        # Configuration files
├── MyFiles/            # Distributed / synchronized files
├── migrations/         # PHP migrations
├── MigrationsSQL/      # SQL migrations
├── logs/               # Application logs
├── template/           # Base templates
├── vendor/             # Composer dependencies
├── index.php           # Web entry point
├── cli.php             # CLI entry point
├── install.php         # Installation script
├── composer.json

```
---
🔌 Plugin System

Maravel includes an advanced Plugin System, inspired by the best CMS platforms but adapted to an MVC framework.

A plugin can:
	•	Register routes
	•	Add menu entries
	•	Hook into events
	•	Provide controllers, models, and views
	•	Extend the application without modifying the Core

👉 This allows building modular and toggleable features.

⸻

🔔 Event Manager

The framework integrates an Event Manager that allows you to:
	•	Decouple application logic
	•	React to domain events
	•	Extend behavior without modifying existing code

Examples:
	•	profile.completed
	•	listing.created
	•	user.registered

⸻

🔁 Centralized Core Updates (Key Feature)

Maravel Framework includes a centralized core update system, designed to manage multiple projects based on the same framework.

With a single command you can:
	•	Scan the entire Core
	•	Generate a structured representation (core.json)
	•	Update shared files
	•	Automatically distribute the updated core to all child projects

Command
```bash
php build-core-json.php

✅ Framework core scanned
✅ MyFiles updated
✅ core.json generated
```
Benefits
	•	🔁 Update the core only once
	•	🧩 No impact on App/ or plugins
	•	🚀 Continuous framework evolution
	•	🛡️ Reduced errors and project divergence
	•	🏢 Ideal for SaaS platforms, multi-site portals, and agencies

⸻

🧪 CLI

Maravel includes a dedicated CLI entry point (cli.php) for:
	•	Migrations
	•	Maintenance operations
	•	Internal scripts
	•	Automated tasks

Easily extensible with custom commands.

⸻

🔐 Security
	•	Protection against direct access
	•	Centralized authentication
	•	Session handling
	•	Public / private area separation

(Extendable with CSRF protection, rate limiting, middleware, etc.)

⸻

⚙️ Requirements
	•	PHP ≥ 8.3
	•	Common PHP extensions (PDO, JSON, mbstring)
	•	Composer
	•	MySQL / MariaDB database

⸻

🛣️ Roadmap (Evolving)
	•	Middleware system
	•	Response object
	•	Validation layer
	•	Dependency Injection Container
	•	Core versioning
	•	Differential updates and rollback
	•	API / JSON mode

⸻

🧠 Open Source Philosophy

Maravel Framework is open source and actively developed in real-world projects.

The core framework is public by design.
Business logic, client-specific modules, and commercial plugins are intentionally kept private.

This ensures:
	•	a stable and evolving core
	•	transparency
	•	freedom to extend Maravel in any direction

⸻

👤 Author

Marco Dattisi
Software Engineer / Web Developer