Contributing
------------

Ting is an open source project.

If you'd like to contribute, please respect the following standards :

* [**Code formating**][1]: Ting completely follows the PSR-2 standard.
Be sure that your code does so.
* **Unit tests**: Ting has a good code coverage for every feature, using [atoum][2].
Please add relevant tests to cover your new feature.
* **Backward Compatibility**: Ting follows the [semver][3] standard. The main principle is that a minor version can not bring any BC Break. Please be sure that you chose the correct target version for your patch.
If your feature needs a BC Break, we strongly encourage you to discuss it through the [issues][4] before to write any code. We'll discuss about the release of the next major version.
* **Pull Request Template**: The description of your pull request must contains the following header (after the explaination):

```markdown
| Q             | A
| ------------- | ---
| Bug fix?      | yes/no
| New feature?  | yes/no
| BC breaks?    | yes/no
| Deprecations? | yes/no
| Tests pass?   | yes
| Licence       | Apache-2.0
| Fixed tickets | #1234
```
[1]: http://www.php-fig.org/psr/psr-2/
[2]: http://docs.atoum.org/fr/latest/
[3]: http://semver.org/
[4]: https://bitbucket.org/ccmbenchmark/ting/issues