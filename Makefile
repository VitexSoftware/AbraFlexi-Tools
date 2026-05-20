# vim: set tabstop=8 softtabstop=8 noexpandtab:
.PHONY: help
help: ## Displays this list of targets with descriptions
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: static-code-analysis
static-code-analysis: vendor ## Runs a static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyse --configuration=phpstan-default.neon.dist --memory-limit=-1

.PHONY: static-code-analysis-baseline
static-code-analysis-baseline: check-symfony vendor ## Generates a baseline for static code analysis with phpstan/phpstan
	vendor/bin/phpstan analyze --configuration=phpstan-default.neon.dist --generate-baseline=phpstan-default-baseline.neon --memory-limit=-1

.PHONY: tests
tests: vendor
	vendor/bin/phpunit tests

.PHONY: vendor
vendor: composer.json composer.lock ## Installs composer dependencies
	composer install

.PHONY: cs
cs: ## Update Coding Standards
	vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --verbose

deb: ## Build debian package
	debuild -us -uc
	

WEBHOOK_URL ?= http://vyvojar.spoje.net:1880/abraflexi-benchmark
BENCHMARK_RESULT ?= /tmp/abraflexi-benchmark-result.json

.PHONY: benchmark
benchmark: ## Run AbraFlexi benchmark against server configured in .env
	php src/benchmark.php -c 100 -e .env -o $(BENCHMARK_RESULT)
	@if [ -n "$(WEBHOOK_URL)" ] && [ -f "$(BENCHMARK_RESULT)" ]; then \
		jq '{winstrom: {"@globalVersion": "1", "changes": [. + {"@evidence": "benchmark", "@operation": "run", "@timestamp": .timestamp}]}}' \
			$(BENCHMARK_RESULT) \
		| curl -s -X POST -H "Content-Type: application/json" -d @- "$(WEBHOOK_URL)" \
		&& echo "Benchmark result sent to $(WEBHOOK_URL)"; \
	fi

.PHONY: validate-multiflexi-app
validate-multiflexi-app: ## Validates the multiflexi JSON
	@if [ -d multiflexi ]; then \
		for file in multiflexi/*.multiflexi.app.json; do \
			if [ -f "$$file" ]; then \
				echo "Validating $$file"; \
				multiflexi-cli app validate-json --file="$$file"; \
			fi; \
		done; \
	else \
		echo "No multiflexi directory found"; \
	fi

