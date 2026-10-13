# Articles AI Enricher

Projet Symfony de type POC visant à démontrer un pipeline d'enrichissement asynchrone d'articles via une analyse IA.

Le but est de montrer une architecture Symfony cohérente autour de Doctrine, Messenger, gestion d'état et traçabilité des traitements, sans prétendre à une version production complète.

---

## Vue d'ensemble

Cette application expose un point d'entrée HTTP qui :

- crée un article en statut `PENDING` ;
- envoie un message asynchrone dans Symfony Messenger ;
- traite ensuite l'article dans un handler dédié ;
- enrichit le contenu avec des données structurées (localité, résumé, tags) ;
- met à jour l'état de l'article et enregistre un historique de traitement.

---

## Stack technique

- PHP 8.4
- Symfony 8.1
- Doctrine ORM
- Messenger
- SQLite pour le développement local et les tests
- PHPUnit
- PHPStan

---

## Architecture

- `ArticleController` : point d'entrée HTTP
- `Article` : entité métier principale
- `EnrichArticleMessage` : message asynchrone
- `EnrichArticleMessageHandler` : traitement métier
- `ArticleAnalyzerInterface` : abstraction autour du service d'analyse
- `ArticleProcessingLog` : journal d’audit des états
- `ArticleStatus` : enum des statuts du cycle de vie

### Cycle de vie

```text
POST /articles/demo
        ↓
ArticleController
        ↓
Persistance de l'article en PENDING
        ↓
Dispatch Messenger
        ↓
EnrichArticleMessageHandler
        ↓
PROCESSING → analyse IA → ENRICHED ou FAILED
        ↓
Journal d'audit dans ArticleProcessingLog
```

### Design choices visibles dans le projet

- Interface `ArticleAnalyzerInterface` pour abstraire l'analyseur réel ou factice
- DTO `ArticleAnalysisResult` pour transporter les résultats structurés
- Entité `Article` séparée des objets de traitement et des logs
- Gestion explicite des états métier : `PENDING`, `PROCESSING`, `ENRICHED`, `FAILED`

---

## Fonctionnement local

### Prérequis

- PHP 8.4+
- Composer
- Symfony CLI (optionnel mais recommandé)

### Installation

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Démarrage

```bash
symfony serve
# ou
php -S 127.0.0.1:8000 -t public
```

### Exemple de démonstration

```bash
curl -X POST http://127.0.0.1:8000/articles/demo
```

---

## Tests et validation

Le projet contient des tests PHPUnit de base pour vérifier :

- la création d’un article via l’API ;
- la gestion d’un identifiant invalide ;
- le comportement du handler d’enrichissement.

### Commandes utiles

```bash
php bin/phpunit
php vendor/bin/phpstan analyse src tests --no-progress --error-format=table
```

### Niveau de qualité actuel

Le projet est fonctionnel et démontre de bonnes bases Symfony, mais il reste un POC / apprentissage technique plutôt qu’un projet “production-ready”.

Les validations automatiques montrent notamment que :

- les tests PHPUnit passent ;
- PHPStan signale encore plusieurs points à corriger avant un niveau de qualité plus élevé.

Cela reste un bon signal pour un portfolio technique, mais il faut le présenter honnêtement comme un projet de démonstration, pas comme un service prêt pour la production.

---

## Points à améliorer pour un portfolio plus crédible

- corriger les erreurs PHPStan restantes ;
- ajouter plus de tests sur les cas limites ;
- clarifier les règles de sécurité et validation des entrées ;
- sécuriser les variables d’environnement et ne pas committer les fichiers de config locaux ;
- documenter plus précisément les limites du POC et les prochaines évolutions.

---

## Conclusion

Ce projet montre une bonne compréhension de :

- Symfony MVC + Doctrine ;
- traitement asynchrone avec Messenger ;
- architecture orientée événements ;
- gestion d’état métier ;
- tests fonctionnels basiques.

C’est un bon projet de démonstration pour une candidature, à condition d’être présenté comme un POC technique clair et structuré, sans sur-prononcer le mot “production-ready”.