.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

ifdef NODOCKER
	PHP = php
	COMPOSER = composer
else
	PHP = ./docker/bin/php
	COMPOSER = ./docker/bin/composer
endif

ifndef COVERAGE
	COVERAGE = --coverage-html ./coverage
endif

ifdef FILTER
	PHPUNIT_FILTER = --filter=$(FILTER)
else
	PHPUNIT_FILTER =
endif

ifdef FILE
	PHPUNIT_FILE = $(FILE)
else
	PHPUNIT_FILE = ./tests
endif

.PHONY: docker-start
docker-start: ## Start a development server
	@echo "Running webserver on http://localhost:8000"
	docker-compose -p webubbub -f docker/docker-compose.yml up

.PHONY: docker-build
docker-build: ## Rebuild the Docker containers
	docker-compose -p webubbub -f docker/docker-compose.yml build

.PHONY: docker-stop
docker-stop: ## Clean the Docker stuff
	docker-compose -p webubbub -f docker/docker-compose.yml down

.PHONY: install
install: ## Install the dependencies
	$(COMPOSER) install

.PHONY: init
init: .env ## Initialize the application
	$(PHP) cli --request /system/init

.PHONY: create-migration
create-migration: ## Create a migration file
	MIGRATION_NAME=$(shell date +%Y%m%d_%H%M%S)_$(NAME) ;\
	MIGRATION_FILE=src/migrations/$${MIGRATION_NAME}.php ;\
	cp docs/migration.template.php $${MIGRATION_FILE} ;\
	sed -i s/{NAMESPACE}/$${MIGRATION_NAME}/ $${MIGRATION_FILE} ;\
	$(EDITOR) $${MIGRATION_FILE}

.PHONY: migrate
migrate: ## Apply pending migrations
	$(PHP) cli --request /system/migrate

.PHONY: test
test: ## Run the tests suite
	XDEBUG_MODE=coverage $(PHP) ./vendor/bin/phpunit \
		$(COVERAGE) --whitelist ./src \
		--bootstrap ./tests/bootstrap.php \
		--testdox \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: ## Run the linter on the PHP files
	$(PHP) ./vendor/bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: ## Fix the errors raised by the linter
	$(PHP) ./vendor/bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.env:
	@cp env.sample .env
