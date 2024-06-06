.PHONY: help
help: ## Displays this list of targets with descriptions
	@echo "The following commands are available:\n"
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: docs
docs: ## Generate projects documentation (from "Documentation" directory)
	mkdir -p Documentation-GENERATED-temp

	docker run --rm --pull always -v "$(shell pwd)":/project -t ghcr.io/typo3-documentation/render-guides:latest --config=Documentation

.PHONY: test-docs
test-docs: ## Test the documentation rendering
	mkdir -p Documentation-GENERATED-temp

	docker run --rm --pull always -v "$(shell pwd)":/project -t ghcr.io/typo3-documentation/render-guides:latest --config=Documentation --no-progress --fail-on-log

.PHONY: install
install: ## Run rector
	Build/Scripts/runTests.sh -s composerUpdate

.PHONY: fix-cgl
fix-cgl: ## Fix PHP coding styles
	Build/Scripts/runTests.sh -s cgl

.PHONY: fix
fix: fix-cgl## Run all fixes

.PHONY: test-cgl
test-cgl: ## Fix PHP coding styles
	Build/Scripts/runTests.sh -s cgl

.PHONY: test-unit-7-4
test-unit-7-4: ## Run unit tests with PHP 7.4 (lowest)
	Build/Scripts/runTests.sh -s unit -p 7.4

.PHONY: test-unit-8-3
test-unit-8-3: ## Run unit tests with PHP 8.3 (highest supported by TYPO3 11)
	Build/Scripts/runTests.sh -s unit -p 8.3

.PHONY: test-unit
test-unit: test-unit-7-4 test-unit-8-3## Run unit tests with PHP 7.4 and 8.3

.PHONY: test-functional-7-4
test-functional-7-4: ## Run functional tests with PHP 7.4 and mariadb (lowest)
	Build/Scripts/runTests.sh -s functional -p 7.4 -d mysql

.PHONY: test-functional-8-3
test-functional-8-3: ## Run functional tests with PHP 8.3 and mariadb (highest supported by TYPO3 11)
	Build/Scripts/runTests.sh -s functional -p 8.3 -d mysql

.PHONY: test-functional
test-functional: test-functional-7-4 test-functional-8-3## Run functional tests with PHP 7.4 and 8.3

.PHONY: phpstan
phpstan: ## Run phpstan tests
	Build/Scripts/runTests.sh -s phpstan

.PHONY: phpstan-baseline
phpstan-baseline: ## Update the phpstan baseline
	Build/Scripts/runTests.sh -s phpstanBaseline

.PHONY: test
test: test-cgl phpstan test-docs test-unit test-functional## Run all tests
