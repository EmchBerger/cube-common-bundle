# run "make" or "make help" to see the help

.DEFAULT_GOAL := help

help:
	@awk 'BEGIN {FS = ":.*?## "}; /^[a-zA-Z-]+:.*?## .*$$/ {printf "\x1b[32m%-15s\x1b[0m %s\n", $$1, $$2}' Makefile | sort
.PHONY: help

###> cubetools/cube-common-develop ###

# development helpers
# ===================

checkScript ?= vendor/bin/check-commit-cube.sh
composer ?= $(shell which composer || echo composer.phar)
syConsole ?= bin/console
cubeDevDir ?= vendor/cubetools/cube-common-develop/

# commit checks

check-lastCommit: ## check last done (HEAD) commit
	$(checkScript) HEAD
.PHONY: check-lastCommit

check-changes: ## check changed files
	$(checkScript) --changed
.PHONY: check-changes

check-precommit: ## check all added files (after git add)
	$(checkScript)
.PHONY: check-precommit

check-branch: ## checks all files changed since origin/development
	$(checkScript) origin/development..
.PHONY: check-branch

# validate targets

ifeq (,$(wildcard(.phpstan.neon)))
stanConfig = ''
else
stanConfig = -c .phpstan.neon
endif
validate-stan: ## runs phpstan (missing variables, wrong case, ...)
	./vendor/bin/phpstan analyse $(stanConfig) src/
.PHONY: validate-stan

validate-codestyle: ## runs phpcs (code style)
	./vendor/bin/phpcs --colors src/
.PHONY: validate-codestyle

validate-cs-fixer: ## runs php-cs-fixer (code style)
	./vendor/bin/php-cs-fixer fix -v --ansi --dry-run --diff
.PHONY: validate-cs-fixer

validate-all: validate-codestyle validate-cs-fixer validate-stan ## runs all validation-* commands
.PHONY: validate-all

.phpstan_baseline.neon: make-phpstan-baseline
make-phpstan-baseline: ## updates ./.phpstan_baseline.neon, please check before committing
	vendor/bin/phpstan analyse --error-format baselineNeon $$(git ls-files '*.php') > .phpstan_baseline.neon || true
	@echo .phpstan_baseline.neon updated, please check it before committing
.PHONY: make-phpstan-baseline

# general

update-makefile-from-cube-common-develop: ## update the makefile section from cube-common-develop

-include $(cubeDevDir)/src/Workplace/Makefile.include.cube

###< cubetools/cube-common-develop ###
