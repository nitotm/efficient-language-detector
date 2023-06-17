default: test

init: get-phpcpd composer-update

test: rector phpcpd phpmd phpstan psalm

rector:
	./vendor/bin/rector -n

phpcpd:
	./phpcpd.phar src

phpmd:
	./vendor/bin/phpmd src text cleancode,codesize,controversial,design,unusedcode

phpstan:
	php ./vendor/bin/phpstan

psalm:
	./vendor/bin/psalm

get-phpcpd:
	wget https://phar.phpunit.de/phpcpd.phar
	chmod +x ./phpcpd.phar

composer-update:
	composer update
fix: fix-rector

fix-rector:
	./vendor/bin/rector

