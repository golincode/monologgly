REPORT_DIR=./report

dump-autoload:
	@composer dump-autoload

test: dump-autoload
	@php ./vendor/bin/phpunit

coverage: dump-autoload
	@./vendor/bin/phpunit --coverage-html $(REPORT_DIR)
