# ─────────────────────────────────────────────────────────────
#  Equencia Backend — Makefile
#  Point d'entrée unique pour tous les développeurs
#  Usage : make <commande>
# ─────────────────────────────────────────────────────────────

COMPOSE = docker compose -f infrastructure/docker-compose.yml
PHP_AUTH = $(COMPOSE) exec auth php
PHP_RH   = $(COMPOSE) exec rh php
PHP_OPS  = $(COMPOSE) exec ops php

.DEFAULT_GOAL := help

# ── Aide ─────────────────────────────────────────────────────
.PHONY: help
help:
	@echo ""
	@echo "  Equencia Backend — Commandes disponibles"
	@echo ""
	@echo "  DÉMARRAGE"
	@echo "  make setup        Premier démarrage complet (clés JWT + build + migrate)"
	@echo "  make dev          Démarrer tous les services"
	@echo "  make stop         Arrêter tous les services"
	@echo "  make restart      Redémarrer tous les services"
	@echo "  make build        Rebuilder les images Docker"
	@echo "  make logs         Logs de tous les services (live)"
	@echo ""
	@echo "  SERVICES INDIVIDUELS"
	@echo "  make logs-auth    Logs du auth-service"
	@echo "  make logs-rh      Logs du rh-service"
	@echo "  make logs-ops     Logs du ops-service"
	@echo "  make sh-auth      Shell dans auth-service"
	@echo "  make sh-rh        Shell dans rh-service"
	@echo "  make sh-ops       Shell dans ops-service"
	@echo ""
	@echo "  BASE DE DONNÉES"
	@echo "  make migrate      Migrations tous les services"
	@echo "  make migrate-auth Migrations auth-service"
	@echo "  make migrate-rh   Migrations rh-service"
	@echo "  make migrate-ops  Migrations ops-service"
	@echo "  make db-reset     Réinitialiser la base de données (DANGER)"
	@echo ""
	@echo "  DÉPENDANCES"
	@echo "  make install      Composer install tous les services"
	@echo "  make install-auth Composer install auth-service"
	@echo "  make install-rh   Composer install rh-service"
	@echo "  make install-ops  Composer install ops-service"
	@echo ""
	@echo "  QUALITÉ"
	@echo "  make test         Tests tous les services"
	@echo "  make test-auth    Tests auth-service"
	@echo "  make test-rh      Tests rh-service"
	@echo "  make test-ops     Tests ops-service"
	@echo "  make lint         PHP-CS-Fixer tous les services"
	@echo "  make phpstan      PHPStan tous les services"
	@echo ""
	@echo "  SÉCURITÉ"
	@echo "  make jwt-keys     Générer les clés JWT RS256 (première fois)"
	@echo ""

# ── Setup initial ─────────────────────────────────────────────
.PHONY: setup
setup: jwt-keys build install migrate
	@echo ""
	@echo "  Setup terminé. Lancer 'make dev' pour démarrer."
	@echo ""

# ── Démarrage ────────────────────────────────────────────────
.PHONY: dev
dev:
	$(COMPOSE) up -d
	@echo ""
	@echo "  Services démarrés :"
	@echo "  Gateway  → http://localhost:80"
	@echo "  Auth     → http://localhost:8001"
	@echo "  RH       → http://localhost:8002"
	@echo "  Ops      → http://localhost:8003"
	@echo "  MinIO    → http://localhost:9001 (console)"
	@echo "  Mercure  → http://localhost:3001"
	@echo "  MailHog  → http://localhost:8025"
	@echo ""

.PHONY: stop
stop:
	$(COMPOSE) down

.PHONY: restart
restart: stop dev

.PHONY: build
build:
	$(COMPOSE) build

.PHONY: logs
logs:
	$(COMPOSE) logs -f auth rh ops

.PHONY: logs-auth
logs-auth:
	$(COMPOSE) logs -f auth

.PHONY: logs-rh
logs-rh:
	$(COMPOSE) logs -f rh

.PHONY: logs-ops
logs-ops:
	$(COMPOSE) logs -f ops

# ── Shells ────────────────────────────────────────────────────
.PHONY: sh-auth
sh-auth:
	$(COMPOSE) exec auth sh

.PHONY: sh-rh
sh-rh:
	$(COMPOSE) exec rh sh

.PHONY: sh-ops
sh-ops:
	$(COMPOSE) exec ops sh

.PHONY: sh-db
sh-db:
	$(COMPOSE) exec postgres psql -U equencia -d equencia

# ── Dépendances ───────────────────────────────────────────────
.PHONY: install
install: install-auth install-rh install-ops

.PHONY: install-auth
install-auth:
	$(COMPOSE) exec auth composer install --no-interaction

.PHONY: install-rh
install-rh:
	$(COMPOSE) exec rh composer install --no-interaction

.PHONY: install-ops
install-ops:
	$(COMPOSE) exec ops composer install --no-interaction

# ── Migrations ────────────────────────────────────────────────
.PHONY: migrate
migrate: migrate-auth migrate-rh migrate-ops

.PHONY: migrate-auth
migrate-auth:
	$(PHP_AUTH) bin/console doctrine:migrations:migrate --no-interaction

.PHONY: migrate-rh
migrate-rh:
	$(PHP_RH) bin/console doctrine:migrations:migrate --no-interaction

.PHONY: migrate-ops
migrate-ops:
	$(PHP_OPS) bin/console doctrine:migrations:migrate --no-interaction

.PHONY: migration-diff-auth
migration-diff-auth:
	$(PHP_AUTH) bin/console doctrine:migrations:diff

.PHONY: migration-diff-rh
migration-diff-rh:
	$(PHP_RH) bin/console doctrine:migrations:diff

.PHONY: migration-diff-ops
migration-diff-ops:
	$(PHP_OPS) bin/console doctrine:migrations:diff

.PHONY: db-reset
db-reset:
	@echo "ATTENTION: Cela supprime toutes les données."
	@read -p "Continuer? (oui/non): " confirm && [ "$$confirm" = "oui" ] || exit 1
	$(COMPOSE) down -v
	$(COMPOSE) up -d postgres
	sleep 3
	$(MAKE) migrate

# ── Cache ─────────────────────────────────────────────────────
.PHONY: cc
cc:
	$(PHP_AUTH) bin/console cache:clear
	$(PHP_RH)   bin/console cache:clear
	$(PHP_OPS)  bin/console cache:clear

# ── Tests ─────────────────────────────────────────────────────
.PHONY: test
test: test-auth test-rh test-ops

.PHONY: test-auth
test-auth:
	$(COMPOSE) exec auth php bin/phpunit

.PHONY: test-rh
test-rh:
	$(COMPOSE) exec rh php bin/phpunit

.PHONY: test-ops
test-ops:
	$(COMPOSE) exec ops php bin/phpunit

# ── Qualité ───────────────────────────────────────────────────
.PHONY: lint
lint:
	$(COMPOSE) exec auth composer run php-cs-fixer
	$(COMPOSE) exec rh   composer run php-cs-fixer
	$(COMPOSE) exec ops  composer run php-cs-fixer

.PHONY: phpstan
phpstan:
	$(COMPOSE) exec auth composer run phpstan
	$(COMPOSE) exec rh   composer run phpstan
	$(COMPOSE) exec ops  composer run phpstan

# ── Sécurité — Clés JWT RS256 ────────────────────────────────
.PHONY: jwt-keys
jwt-keys:
	@echo "Génération des clés JWT RS256..."
	@mkdir -p services/auth/config/jwt
	@openssl genrsa -out services/auth/config/jwt/private.pem 4096
	@openssl rsa -pubout -in services/auth/config/jwt/private.pem -out services/auth/config/jwt/public.pem
	@cp services/auth/config/jwt/public.pem services/rh/config/jwt/public.pem
	@cp services/auth/config/jwt/public.pem services/ops/config/jwt/public.pem
	@mkdir -p services/rh/config/jwt services/ops/config/jwt
	@echo "Clés générées et distribuées aux services rh et ops."
	@echo "IMPORTANT: Ne jamais committer private.pem dans Git."
