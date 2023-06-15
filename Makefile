.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

ifdef DOCKER
	PHP = ./docker/bin/php
else
	PHP = php
endif

.PHONY: start
start: ## Start a development server (use Docker)
	@echo "Running webserver on http://localhost:8000"
	docker-compose -p webubbub -f docker/docker-compose.yml up

.PHONY: stop
stop: ## Stop and clean Docker server
	docker-compose -p webubbub -f docker/docker-compose.yml down

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
test: bin/phpunit ## Run the tests suite
	$(PHP) ./bin/phpunit --bootstrap ./tests/bootstrap.php ./tests

.PHONY: lint
lint: bin/phpcs ## Run the linter on the PHP files
	$(PHP) ./bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: bin/phpcbf ## Fix the errors raised by the linter
	$(PHP) ./bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.env:
	@cp env.sample .env

bin/phpunit:
	mkdir -p bin/
	wget -O bin/phpunit https://phar.phpunit.de/phpunit-9.5.19.phar
	echo '8941fa4d4183dc7e5b2582300c120da910135e6e4f1173fb4a9b4b658bd4ae43 bin/phpunit' | sha256sum -c - || rm bin/phpunit

bin/phpcs:
	mkdir -p bin/
	wget -O bin/phpcs https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.6.2/phpcs.phar
	echo 'c0832cdce3e419c337011640ddebd08b7daac32344250ac7cfbc799309506f77 bin/phpcs' | sha256sum -c - || rm bin/phpcs

bin/phpcbf:
	mkdir -p bin/
	wget -O bin/phpcbf https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.6.2/phpcbf.phar
	echo '28d74aaaa7ad251c4ed23481e7fa18b755450ee57872d22be0ecb8fe21f50dc8 bin/phpcbf' | sha256sum -c - || rm bin/phpcbf
