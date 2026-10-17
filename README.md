# clicalmani/foundation

Noyau du framework **Tonka** : conteneur de services, configuration, cache, HTTP,
injection de dépendances des contrôleurs, événements, stockage, authentification,
console et helpers globaux. Namespace racine `Clicalmani\Core\`.

Le package n'est pas une application : il fournit la machinerie qu'un kernel
(HTTP ou console) assemble puis démarre.

## Prérequis

`php ^8.4`

Dépendances principales : `symfony/dependency-injection`, `symfony/config`,
`symfony/cache`, `symfony/event-dispatcher`, `symfony/filesystem`, `symfony/uid`,
`league/flysystem`, `twig/twig`, `nesbot/carbon`, `vlucas/phpdotenv`,
`doctrine/inflector`.

Les packages frères (`clicalmani/routing`, `psr`, `validation`, `console`,
`database`, `metrics`).

## Installation

```bash
composer require clicalmani/foundation
```

En développement local, depuis le dossier `packages/` :

```bash
cd foundation
composer install
```

## Tests

```bash
composer test          # = phpunit
```

## Licence

MIT — voir [LICENSE](LICENSE).
