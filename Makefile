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

.PHONY: setup
setup: .env ## Initialize the application
	$(PHP) cli migrations setup --seed

.PHONY: test
test: ## Run the tests suite
	XDEBUG_MODE=coverage $(PHP) ./vendor/bin/phpunit \
		$(COVERAGE) --whitelist ./src \
		--bootstrap ./tests/bootstrap.php \
		--testdox \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: ## Run the linters on the PHP files
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit 1G -c phpstan.neon
	$(PHP) ./vendor/bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: ## Fix the errors raised by the linter
	$(PHP) ./vendor/bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.env:
	@cp env.sample .env
