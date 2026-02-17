# Tester Test Framework Adapter for Infection

> *Disclaimer: AI agent experiment - chat-gpt5.2 & claude sonnet 4.5 wrote ~60-75% of this project, by my lack of knowledge on this topic and lack of time to learn...
>I thought it would be interesting to ask AI "Hey, I discovered [this framework][nette], and it comes with its [own testing framework][tester]. But I love Infection and its metrics to push you to test your tests, and feel quite sorry it does not support it yet... Are we in ? - yes, Here's your code"
> (it wasn't this simple, [here's some copilot's very verbose journals & reports](docs/copilot-report) for french speakers - it's a real mess, sorry, all dev problems seem to be included)
> and share the result.
>
> The remaining ~30% was based on [PhpSpec Adapter][phpspec-adapter] and [Codeception Adapter][codeception-adapter]. Big Shoutout to Maks Rafalko & Infection community for this amazing tool

This package provides the test framework adapter of [Tester][tester] for [infection][infection].

![Architecture](./docs/tester-adapter.png)

## Installation

In a standard usage, infection should detect [`nette/tester`][tester] being used and
leverage its [`infection/extension-installer`][infection/extension-installer] to install this
package.

Otherwise, you can still install it as usual:

```shell
composer require --dev raneomik/infection-tester-adapter
```

The adapter should be automatically registered in Infection's runtime through its auto-discovery mechanism.

## Usage

Once installed, you can run Infection:

```shell
vendor/bin/infection # optional: --test-framework=tester
```

Infection will automatically detect and use the [Tester][tester] adapter when it is declared in your project.

### Configuration

This adapter should work directly with your existing Tester tests, with limitations :

- Tester provides jUnit.xml and Clover.xml coverage reports, but in a lighter format than the phpunit namespaced expected by Infection.
So it is NOT recomended to use `--skip-initial-tests` with `--coverage` options (it just results in xpath read/FQCN error).
This adapter embeds transformation of those reports during the mutation process,.
- [Test cases](https://tester.nette.org/en/testcase) are encouraged for better report result
- other forms of tests are supported, but need namespaces to be identified by Infection and referenced in report as covering code.

No additional configuration, following Tester's "Convention over configuration" principle.
*("have a bootstrap.php file in your tests root" is the main requirement)*


Infection configuration file `infection.json5.dist`.

For more information on configuring Infection, see the [Infection documentation][infection-configuration-docs].

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](.github/CONTRIBUTING.md) for details.

## License

This project is licensed under the BSD 3-Clause License. See the [LICENSE](LICENSE) file for details.

[infection]: https://infection.github.io
[infection-configuration-docs]: https://infection.github.io/guide/usage.html#Configuration
[infection/extension-installer]: https://packagist.org/packages/infection/extension-installer
[nette]: https://nette.org
[tester]: https://tester.nette.org/en
[phpspec-adapter]: https://github.com/infection/phpspec-adapter
[codeception-adapter]: https://github.com/infection/codeception-adapter

