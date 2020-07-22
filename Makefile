.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

ifdef DOCKER
	PHP = docker-compose -f docker/docker-compose.yml run --no-deps php php
else
	PHP = php
endif

.PHONY: start
start: ## Start a development server (use Docker)
	@echo "Running webserver on http://localhost:8000"
	docker-compose -f docker/docker-compose.yml up

.PHONY: stop
stop: ## Stop and clean Docker server
	docker-compose -f docker/docker-compose.yml down

.PHONY: init
init: .env ## Initialize the application
	$(PHP) ./webubbub --request /system/init

.PHONY: create-migration
create-migration: ## Create a migration file
	MIGRATION_NAME=$(shell date +%Y%m%d_%H%M%S)_$(NAME) ;\
	MIGRATION_FILE=src/migrations/$${MIGRATION_NAME}.php ;\
	cp docs/migration.template.php $${MIGRATION_FILE} ;\
	sed -i s/{NAMESPACE}/$${MIGRATION_NAME}/ $${MIGRATION_FILE} ;\
	$(EDITOR) $${MIGRATION_FILE}

.PHONY: migrate
migrate: ## Apply pending migrations
	$(PHP) ./webubbub --request /system/migrate

.PHONY: test
test: ## Run the tests suite
	$(PHP) ./bin/phpunit --bootstrap ./tests/bootstrap.php ./tests

.PHONY: lint
lint: ## Run the linter on the PHP files
	$(PHP) ./bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: ## Fix the errors raised by the linter
	$(PHP) ./bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.env:
	@cp env.sample .env
