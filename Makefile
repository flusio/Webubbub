.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

DOCKER_COMPOSE = docker compose -f docker/development/docker-compose.yml

ifdef NODOCKER
	PHP = php
	COMPOSER = composer
else
	PHP = ./docker/bin/php
	COMPOSER = ./docker/bin/composer
endif

.PHONY: docker-start
docker-start: ## Start a development server
	@echo "Running webserver on http://localhost:8000"
	$(DOCKER_COMPOSE) up

.PHONY: docker-build
docker-build: ## Rebuild the Docker image
	$(DOCKER_COMPOSE) build

.PHONY: docker-clean
docker-clean: ## Clean the Docker stuff
	$(DOCKER_COMPOSE) down

.PHONY: install
install: ## Install the dependencies
	$(COMPOSER) install

.PHONY: setup
setup: .env ## Initialize the application
	$(PHP) cli migrations setup --seed

.PHONY: test
test: FILE ?= ./tests
ifdef FILTER
test: override FILTER := --filter=$(FILTER)
endif
test: COVERAGE ?= --coverage-html ./coverage
test: ## Run the test suite (can take FILE, FILTER and COVERAGE arguments)
	$(PHP) ./vendor/bin/phpunit -c .phpunit.xml $(COVERAGE) $(FILTER) $(FILE)

.PHONY: lint
lint: LINTER ?= all
lint: ## Run the linters on the PHP files (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all phpstan))
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit 1G -c .phpstan.neon
endif
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) ./vendor/bin/phpcs
endif

.PHONY: lint-fix
lint-fix: ## Fix the errors raised by the linter (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) ./vendor/bin/phpcbf
endif

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.env:
	@cp env.sample .env
