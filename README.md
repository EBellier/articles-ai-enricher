# Articles AI Enricher (POC to Production-Ready)

Application Symfony 7 pipeline d'enrichissement asynchrone d'articles via analyse ia (LLM) | architecture événementielle / message bus, gestion fine de la résilience, traçabilité d'audit et couverture de tests.

---

## Architecture & Choix techniques

- **Langage & Framework** : PHP 8.3+ / Symfony 7
- **Persistance** : Doctrine ORM, SQLite (local & test)
- **Traitement asynchrone** : Symfony Messenger (transport Doctrine en local, `in-memory` en test)
- **Design Patterns** :
  - **Strategy / Adapter** : Interface `ArticleAnalyzerInterface` permettant d'intervertir un analyseur factice (mock/fake) et des clients LLM réels (OpenAI, Mistral) via injection de dépendances, sans modifier la logique métier.
  - **Data Mapper** : Séparation stricte du domaine et de la persistance via Doctrine.
  - **DTO Pattern** : Échanges typés et immuables (`ArticleAnalysisResult`).
- **Résilience & Cycle de vie** :
  - Cycle d'états strict : `PENDING` → `PROCESSING` → `ENRICHED` ou `FAILED`.
  - Capture des exceptions techniques et audit systématique des tentatives dans `ArticleProcessingLog` (`onDelete: CASCADE`).
  - Politique de retry avec backoff exponentiel pilotée par Symfony Messenger (`max_retries: 3`).
- **Qualité & Tests** : Suite automatisée sous PHPUnit (tests unitaires sur le Handler, tests fonctionnels sur l'API HTTP).

---

## Cycle de vie d'un traitement

```text
[Client HTTP]
      │ POST /articles/demo
      ▼
[ArticleController] ── (Persist PENDING) ──► [Base SQLite]
      │
      └─► [Message Bus] ──► [Transport async: SQLite]
                                   │
                           (messenger:consume)
                                   ▼
                   [EnrichArticleMessageHandler]
                         │
                         ├─► Statut PROCESSING + Log d'audit
                         │
                         ├─► [ArticleAnalyzerInterface] (LLM API / Fake)
                         │         │
                         │         ├─► Succès : Statut ENRICHED + Métadonnées + Log
                         │         │
                         │         └─► Échec  : Statut FAILED + Log d'erreur technique
                         │                      └──► Retry Messenger (backoff)