.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

.PHONY: start
start: ## Start a development server (use Docker)
	@echo "Running webserver on http://localhost:8000"
	docker-compose -f docker/docker-compose.yml up

.PHONY: stop
stop: ## Stop and clean Docker server
	docker-compose -f docker/docker-compose.yml down

.PHONY: test
test: ## Run the tests suite
	docker-compose -f docker/docker-compose.yml run --no-deps php ./bin/phpunit --bootstrap ./tests/bootstrap.php ./tests

.PHONY: lint
lint: ## Run the linter on the PHP files
	docker-compose -f docker/docker-compose.yml run --no-deps php ./bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: ## Fix the errors raised by the linter
	docker-compose -f docker/docker-compose.yml run --no-deps php ./bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
