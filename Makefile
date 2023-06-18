default: test

init: get-phpcpd composer-update

validate: rector phpcpd phpmd phpstan psalm

rector:
	./vendor/bin/rector -n

phpcpd:
	./phpcpd.phar src

phpmd:
	./vendor/bin/phpmd src text codesize,controversial,design,unusedcode
	./vendor/bin/phpmd src text cleancode

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

benchmark:
	php benchmark.php

test:
	php tests.php

example:
	php index.php
