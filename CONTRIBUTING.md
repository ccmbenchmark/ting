Contribuer
------------

Ting est un projet interne CCMBenchmark, hébergé sur GitLab.

Si vous souhaitez contribuer, merci de respecter les standards suivants :

* [**Formatage du code**][1] : Ting suit intégralement le standard PSR-2. Assurez-vous que votre code fait de même.
* **Tests unitaires** : Ting a une bonne couverture de code pour chaque fonctionnalité, en utilisant [atoum][2]. Merci d'ajouter les tests pertinents pour couvrir votre nouvelle fonctionnalité.
* **Compatibilité ascendante (BC)** : Ting suit le standard [semver][3]. Le principe principal est qu'une version mineure ne doit apporter aucun BC Break. Assurez-vous de choisir la bonne version cible pour votre patch.
Si votre fonctionnalité nécessite un BC Break, nous vous encourageons fortement à en discuter via les [issues][4] avant d'écrire du code. Nous discuterons alors de la sortie de la prochaine version majeure.
* **Template de Merge Request** : la description de votre merge request doit contenir l'en-tête suivant (après l'explication) :

```markdown
| Q                    | A
| -------------------- | ---
| Correction de bug ?  | oui/non
| Nouvelle fonctionnalité ? | oui/non
| BC break ?           | oui/non
| Dépréciations ?      | oui/non
| Tests passants ?     | oui
| Licence              | Apache-2.0
| Tickets corrigés     | #1234
```
[1]: http://www.php-fig.org/psr/psr-2/
[2]: http://docs.atoum.org/fr/latest/
[3]: http://semver.org/
[4]: https://gitlab.ccmbg.com/core/ting/-/issues
