ifndef APP_ENV
	-include .env
endif

.DEFAULT_GOAL := help

.PHONY: help
help:
	@awk 'BEGIN {FS = ":.*?## "}; /^[a-zA-Z-]+:.*?## .*$$/ {printf "\033[32m%-15s\033[0m %s\n", $$1, $$2}' Makefile | sort

.PHONY: up
up: ## Start containers
	@docker-compose up -d

.PHONY: down
down: ## Stop containers
	@docker-compose down

.PHONY: fpm
fpm: ## Fpm bash
	@docker-compose exec -u $(USER_ID):$(GROUP_ID) fpm ash

.PHONY: fpm-root
fpm-root: ## Root fpm bash
	@docker-compose exec fpm ash

.PHONY: cli
cli: ## Cli bash
	@docker-compose exec -u $(USER_ID):$(GROUP_ID) cli ash

.PHONY: cli-root
cli-root: ## Root cli bash
	@docker-compose exec cli ash

.PHONY: install
install: ## Composer install
	@docker-compose exec -u $(USER_ID):$(GROUP_ID) cli composer install

.PHONY: test
test: ## Run unit tests
	@docker-compose exec -u $(USER_ID):$(GROUP_ID) cli composer test