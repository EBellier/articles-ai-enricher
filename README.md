# Articles AI Enricher

Projet Symfony de type POC visant à démontrer un flux d’enrichissement asynchrone d’articles via analyse IA.

L’objectif est de montrer une architecture Symfony cohérente autour de Doctrine ORM, Messenger, gestion d’état métier et traçabilité des traitements, sans prétendre à une version production complète.

---

## Vue d’ensemble

L’application expose un petit flux backend orienté démonstration :

- création d’un article en statut `PENDING` ;
- envoi d’un message asynchrone via Symfony Messenger ;
- traitement dans un handler dédié ;
- enrichissement des données de l’article avec des informations structurées ;
- mise à jour du statut et journalisation des étapes.

Le projet contient aussi un point d’accès pour récupérer un article par ID.

---

## Stack technique

- PHP 8.4
- Symfony 8.1
- Doctrine ORM
- Symfony Messenger
- SQLite pour le développement local et les tests
- PHPUnit
- PHPStan
- API Gemini via Symfony HTTP Client

---

## Architecture

### Composants principaux

- `ArticleController` : point d’entrée HTTP
- `Article` : entité métier principale
- `ArticleProcessingLog` : historique des changements de statut
- `EnrichArticleMessage` : message asynchrone
- `EnrichArticleMessageHandler` : traitement principal
- `ArticleAnalyzerInterface` : abstraction du service d’analyse
- `GeminiArticleAnalyzer` : analyseur réel via l’API Gemini
- `FakeArticleAnalyzer` : implémentation de test sans appel réseau
- `ArticleStatus` : enum des statuts métier

### Flux métier

```text
POST /articles/demo
        ↓
ArticleController
        ↓
Création de l'article en PENDING
        ↓
Dispatch Messenger
        ↓
EnrichArticleMessageHandler
        ↓
PROCESSING → analyse IA → ENRICHED ou FAILED
        ↓
Mise à jour de l'article + historique dans ArticleProcessingLog
```

### Design visible dans le code

- abstraction du service d’analyse via `ArticleAnalyzerInterface` ;
- DTO `ArticleAnalysisResult` pour transporter les résultats structurés ;
- séparation claire entre domaine, persistance et traitement asynchrone ;
- gestion explicite des états métier : `PENDING`, `PROCESSING`, `ENRICHED`, `FAILED`.

---

## Routes disponibles

### POST /articles/demo
Crée un article de démonstration, le sauvegarde en base et envoie un message pour traitement asynchrone.

### GET /articles/{id}
Récupère un article avec ses données enrichies et son statut.

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

### Démarrage

```bash
symfony serve
# ou
php -S 127.0.0.1:8000 -t public
```

### Exemple d’usage

```bash
curl -X POST http://127.0.0.1:8000/articles/demo
curl http://127.0.0.1:8000/articles/1
```

Le traitement est asynchrone. Le POST renvoie l’article créé avec statut initial, puis le worker traite le message dans le flux Messenger.

---

## Intégration IA

Le service réel d’analyse est `GeminiArticleAnalyzer`.

En environnement normal / développement, le projet utilise l’API Gemini en fonction des variables d’environnement :

```bash
GEMINI_API_KEY=...
GEMINI_MODEL=gemini-flash-latest
```

En environnement de test, le conteneur remplace l’interface par `FakeArticleAnalyzer` pour éviter les appels réseau et rendre les tests reproductibles.

---

## Tests et validation

Le projet contient des tests PHPUnit qui couvrent :

- la création d’un article via l’API ;
- la récupération d’un article existant ;
- le flux complet : création → message → enrichissement asynchrone ;
- le comportement du handler d’enrichissement.

### Commandes utiles

```bash
php bin/phpunit
php vendor/bin/phpstan analyse src tests --no-progress --error-format=table
```

### Traitement asynchrone en local

Pour consommer les messages asynchrones :

```bash
php bin/console messenger:consume async -vv
```

---



