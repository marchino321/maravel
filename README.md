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

	✅	Register routes
	✅	Add menu entries
	✅	Hook into events
	✅	Provide controllers, models, and views
	✅	Extend the application without modifying the Core

👉 This allows building modular and toggleable features.

🔌 ExamplePlugin

Maravel includes a minimal ExamplePlugin to demonstrate how the plugin system works and how features can be added without touching the Core.

The goal of this plugin is educational: it shows the full lifecycle of a plugin in the simplest possible way.



📁 Plugin Structure

```text
App/
└── Plugins/
    └── ExamplePlugin/
        ├── ExamplePlugin.php
        ├── ExampleController.php
        └── Views/
            └── index.html.twig
```
🧩 ExamplePlugin.php — Plugin bootstrap
```php
<?php

namespace App\Plugins\ExamplePlugin;

use Core\PluginController;
use Core\Router;
use Core\EventManager;

class ExamplePlugin extends PluginController
{
    public function register(): void
    {
        // Register a route provided by the plugin
        Router::get('/example', [
            'controller' => ExampleController::class,
            'action'     => 'index'
        ]);

        // Hook into a framework event (example)
        EventManager::on('app.booted', function () {
            // Custom logic executed when the application boots
        });
    }
}
```
This file shows:

	⭐	how a plugin is registered
	⭐	how routes are defined inside a plugin
	⭐	how events can be hooked without modifying the Core

🎮 ExampleController.php — Plugin controller

```php

<?php

namespace App\Plugins\ExamplePlugin;

use Core\Controller;

class ExampleController extends Controller
{
    public function index(): void
    {
        echo $this->twigManager
            ->getTwig()
            ->render('ExamplePlugin/index.html.twig', [
                'title'   => 'Hello from ExamplePlugin',
                'message' => 'This page is rendered by a Maravel plugin.'
            ]);
    }
}
```
This controller behaves exactly like an App controller, proving that plugins are first-class citizens in Maravel.

🖼️ index.html.twig — Plugin view
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ title }}</title>
</head>
<body>

<h1>{{ title }}</h1>
<p>{{ message }}</p>

<p>
    This page exists only because the plugin is enabled.
</p>

</body>
</html>
```
▶️ How to test the plugin

	1.	Make sure the plugin is enabled (plugins are auto-loaded by default)
	2.	Start your local development server
	3.	Open your browser and visit:
  ```text
  /example
  ```
  You should see a page rendered entirely by the plugin.

🧠 Why plugins matter in Maravel

Plugins allow you to:

	✅	Keep the Core clean and stable
	✅	Encapsulate features
	✅	Enable or disable functionality
	✅	Reuse modules across multiple projects
	✅	Extend the framework without modifying its internals

This approach makes Maravel ideal for long-lived projects, SaaS platforms, and multi-client environments.



📌 Summary

The ExamplePlugin demonstrates:

	⭕	Route registration inside a plugin
	⭕	Controller logic isolated from the Core
	⭕	Twig rendering from plugin views
	⭕	Event-driven extensibility

If you understand this plugin, you understand how Maravel works.





🔔 Event Manager

The framework integrates an Event Manager that allows you to:

	✅	Decouple application logic
	✅	React to domain events
	✅	Extend behavior without modifying existing code

Examples:

	⭕	profile.completed
	⭕	listing.created
	⭕	user.registered
```php

EventManager::dispatch("user.login", $_SESSION);

EventManager::on("user.login", function ($session) {
  $session['Login'] = true;
  return $session;
});
```


🔁 Centralized Core Updates (Key Feature)

Maravel Framework includes a centralized core update system, designed to manage multiple projects based on the same framework.

With a single command you can:

	⭐	Scan the entire Core
	⭐	Generate a structured representation (core.json)
	⭐	Update shared files
	⭐	Automatically distribute the updated core to all child projects

Command
```bash
php build-core-json.php

✅ Framework core scanned
✅ MyFiles updated
✅ core.json generated
```
Benefits

		🔁 Update the core only once
		🧩 No impact on App/ or plugins
		🚀 Continuous framework evolution
		🛡️ Reduced errors and project divergence
		🏢 Ideal for SaaS platforms, multi-site portals, and agencies



🧪 CLI

Maravel includes a dedicated CLI entry point (cli.php) for:

	⭐	Migrations
	⭐	Maintenance operations
	⭐	Internal scripts
	⭐	Automated tasks

Easily extensible with custom commands.



🔐 Security

	🛃	Protection against direct access
	🛃	Centralized authentication
	🛃	Session handling
	🛃	Public / private area separation

(Extendable with CSRF protection, rate limiting, middleware, etc.)



⚙️ Requirements

	✅	PHP ≥ 8.3
	✅	Common PHP extensions (PDO, JSON, mbstring)
	✅	Composer
	✅	MySQL / MariaDB database



🛣️ Roadmap (Evolving)

	❤️	Middleware system
	❤️	Response object
	❤️	Validation layer
	❤️	Dependency Injection Container
	❤️	Core versioning
	❤️	Differential updates and rollback
	❤️	API / JSON mode



🧠 Open Source Philosophy

Maravel Framework is open source and actively developed in real-world projects.

The core framework is public by design.
Business logic, client-specific modules, and commercial plugins are intentionally kept private.

This ensures:

	❤️	a stable and evolving core
	❤️	transparency
	❤️	freedom to extend Maravel in any direction



👤 Author

Marco Dattisi
Software Engineer / Web Developer