# Nette Tester Adapter for Infection

This package provides the test framework adapter for [Nette Tester][tester] for [Infection][infection] mutation testing framework.

[![Build Status](https://img.shields.io/badge/status-active-success.svg)]()
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-blue.svg)]()

## 🚀 Installation

In a standard usage, Infection should detect [`nette/tester`][tester] being used and leverage its [`infection/extension-installer`][infection/extension-installer] to install this package automatically.

Otherwise, you can still install it manually:

```bash
composer require --dev infection/tester-adapter
```

The adapter will be automatically registered in Infection's runtime through its auto-discovery mechanism.

## 📖 Usage

Once installed, you can run Infection:

```bash
vendor/bin/infection
# or explicitly: vendor/bin/infection --test-framework=tester
```

Infection will automatically detect and use the Tester adapter when Nette Tester is configured in your project.

### Configuration

The adapter works with your existing Tester configuration (`tester.yml`). No additional configuration is required beyond the standard Infection configuration file `infection.json5`.

**Example `infection.json5`:**
```json5
{
    "$schema": "vendor/infection/infection/resources/schema.json",
    "source": {
        "directories": ["src"]
    },
    "testFramework": "tester",
    "mutators": {
        "@default": true
    }
}
```

For more information on configuring Infection, see the [Infection documentation][infection-configuration-docs].

## ✨ Features

- ✅ Full Nette Tester integration
- ✅ Automatic code coverage collection (PCOV, PHPDBG, or Xdebug)
- ✅ Parallel test execution support
- ✅ No configuration needed (works out of the box)
- ✅ Modern PSR-4 architecture

## 🔧 Requirements

- PHP 8.2 or higher
- Nette Tester 2.6 or higher
- Infection 0.32 or higher
- One of: PCOV, PHPDBG, or Xdebug (for code coverage)

## 📚 Documentation

Detailed documentation is available in the [`docs/`](docs/) directory:

- **[Usage Guide](docs/USAGE.md)** - Complete usage guide with examples
- **[Architecture](docs/REFACTORING.md)** - Technical architecture details
- **[Testing](docs/TESTING.md)** - Testing procedures
- **[Migration](docs/MIGRATION.md)** - Migration from older versions

## 🏗️ Architecture

This adapter has been completely refactored with a modern PSR-4 architecture:

- **No procedural scripts** - Everything is PSR-4 classes
- **No environment variables** - Configuration embedded in generated files
- **No AST transformations** - Infection handles visibility natively
- **Simple and maintainable** - Clean separation of concerns

See [docs/REFACTORING.md](docs/REFACTORING.md) for details.

## 🐛 Troubleshooting

### No coverage data

Make sure you have PCOV, PHPDBG, or Xdebug installed:

```bash
# Install PCOV (recommended - fastest)
pecl install pcov

# Verify
php -m | grep pcov
```

### Tests fail

Verify your tests pass normally:

```bash
vendor/bin/tester tests/
```

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](.github/CONTRIBUTING.md) for details.

## 📄 License

This project is licensed under the BSD 3-Clause License. See the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Infection Team](https://github.com/infection/infection) - For the excellent mutation testing framework
- [Nette Framework](https://nette.org/) - For the Tester framework
- All contributors

---

[infection]: https://infection.github.io
[infection-configuration-docs]: https://infection.github.io/guide/usage.html#Configuration
[infection/extension-installer]: https://packagist.org/packages/infection/extension-installer
[tester]: https://tester.nette.org/

