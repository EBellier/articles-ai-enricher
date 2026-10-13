# Articles AI Enricher

Projet Symfony de type POC visant à démontrer un flux d'enrichissement asynchrone d'articles via analyse IA.

L'objectif est d'illustrer une architecture Symfony cohérente autour de Doctrine ORM, Messenger, gestion des états métier et traçabilité des traitements, dans un contexte orienté démonstration technique.

---

## Objectif du projet

Cette application simule un workflow éditorial automatisé :

- un article est créé via une route HTTP ;
- il est enregistré avec un statut initial `PENDING` ;
- un message est envoyé dans Symfony Messenger ;
- un handler traite l'article de manière asynchrone ;
- les données enrichies (localité, résumé, tags) sont enregistrées ;
- un historique de traitement est conservé en base.

Le projet met en avant des notions de backend Symfony : orchestration de services, traitement asynchrone, gestion d'état, journalisation et tests fonctionnels.

---

## Stack technique

- PHP 8.4
- Symfony 8.1
- Doctrine ORM
- Symfony Messenger
- SQLite pour le développement local et les tests
- PHPUnit
- PHPStan

---

## Architecture

### Composants principaux

- `ArticleController` : point d’entrée HTTP
- `Article` : entité métier principale
- `EnrichArticleMessage` : message asynchrone
- `EnrichArticleMessageHandler` : traitement métier principal
- `ArticleAnalyzerInterface` : abstraction du service d'analyse
- `GeminiArticleAnalyzer` : intégration réelle avec l’API Gemini
- `FakeArticleAnalyzer` : version de test / démonstration sans dépendance externe
- `ArticleProcessingLog` : audit des étapes de traitement
- `ArticleStatus` : enum du cycle de vie

### Cycle de vie d'un traitement

```text
POST /articles/demo
        ↓
ArticleController
        ↓
Création de l'article en status PENDING
        ↓
Dispatch Message via Messenger
        ↓
EnrichArticleMessageHandler
        ↓
PROCESSING → analyse IA → ENRICHED ou FAILED
        ↓
Historique enregistré dans ArticleProcessingLog
```

---

## Points d’architecture mis en avant

- abstraction du service d’analyse via `ArticleAnalyzerInterface` ;
- utilisation d'un DTO `ArticleAnalysisResult` pour structurer les résultats ;
- séparation claire entre domaine métier, persistance et traitement asynchrone ;
- gestion explicite des états métier : `PENDING`, `PROCESSING`, `ENRICHED`, `FAILED` ;
- audit de traitements via `ArticleProcessingLog`.

---

## Fonctionnement local

### Prérequis

- PHP 8.4+
- Composer
- Symfony CLI (recommandé)

### Installation

```bash
composer install
php bin/console doctrine:migrations:migrate
```

### Lancer l’application

```bash
symfony serve
# ou
php -S 127.0.0.1:8000 -t public
```

### Endpoint de démonstration

```bash
curl -X POST http://127.0.0.1:8000/articles/demo
```

Le endpoint retourne un article créé en statut `pending` et envoie ensuite le message pour traitement asynchrone.

---

## Traitement asynchrone

Le message `EnrichArticleMessage` est consommé par Symfony Messenger.

Pour tester le flux complet localement, il faut lancer le worker :

```bash
php bin/console messenger:consume async -vv
```

En environnement de test, le projet utilise un analyseur factice pour éviter les dépendances externes et rendre les tests reproductibles.

---

## Tests et validation

Le projet contient des tests PHPUnit couvrant :

- la création d’un article via l’API ;
- la lecture d’un article existant ;
- le flux complet de création puis enrichissement ;
- le comportement du handler en cas de succès et d’échec.

### Commandes utiles

```bash
php bin/phpunit
php vendor/bin/phpstan analyse src tests --no-progress --error-format=table
```

### État actuel

Le projet est fonctionnel et valide côté tests et analyse statique. Il constitue un bon POC Symfony orienté backend / architecture asynchrone, mais il reste un projet de démonstration technique plutôt qu’un service prêt pour une production exigeante.

---

## Points d’amélioration possibles

- enrichir la couverture de tests sur des cas limites ;
- sécuriser davantage le traitement de sortie IA ;
- ajouter une vraie stratégie de retry / circuit breaker plus avancée ;
- documenter plus précisément les variables d’environnement de production ;
- isoler encore mieux les tests externes et les tests “smoke” IA.

---

## Conclusion

Ce projet montre une bonne compréhension de :

- Symfony Framework Bundle ;
- Doctrine ORM ;
- Symfony Messenger ;
- design orienté services et abstraction ;
- gestion d’état métier et historique d’audit ;
- tests automatisés et analyse statique.

Il s’agit d’un bon projet de démonstration pour un portfolio ou une candidature, présenté de manière honnête comme un POC technique solide et structuré.
