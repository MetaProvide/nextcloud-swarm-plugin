# This file is licensed under the Affero General Public License version 3 or
# later. See the LICENSE file.

app_name=files_external_beeswarm
build_directory=$(CURDIR)/build
temp_build_directory=$(build_directory)/temp
build_tools_directory=$(CURDIR)/build/tools

all: dev-setup lint

release: build-tarball

dev-setup: clean-dev composer npm-init

lint: eslint stylelint prettier php-cs

lint-fix: eslint-fix stylelint-fix prettier-fix php-cs-fix

# Dependencies
composer:
	composer install --prefer-dist

composer-update:
	composer update --prefer-dist

npm-init:
	npm ci

npm-update:
	npm update

# Linting
eslint:
	npm run eslint

eslint-fix:
	npm run eslint:fix

# Style linting
stylelint:
	npm run stylelint

stylelint-fix:
	npm run stylelint:fix

# Prettier
prettier:
	npm run prettier

prettier-fix:
	npm run prettier:fix

# PHP CS Fixer
php-cs:
	vendor/bin/php-cs-fixer fix -v --dry-run

php-cs-fix:
	vendor/bin/php-cs-fixer fix -v

# Cleaning
clean-dev:
	rm -rf node_modules

build-tarball:
	rm -rf $(build_directory)
	mkdir -p $(temp_build_directory)
	rsync -a \
	--exclude=".git" \
	--exclude=".github" \
	--exclude=".vscode" \
	--exclude="node_modules" \
	--exclude="build" \
	--exclude="vendor" \
	--exclude=".editorconfig" \
	--exclude=".gitignore" \
	--exclude=".php_cs.dist" \
	--exclude=".prettierrc" \
	--exclude=".stylelintrc.json" \
	--exclude="composer.json" \
	--exclude="composer.lock" \
	--exclude="Makefile" \
	--exclude="package-lock.json" \
	--exclude="package.json" \
	../$(app_name)/ $(temp_build_directory)/$(app_name)
	tar czf $(build_directory)/$(app_name).tar.gz \
		-C $(temp_build_directory) $(app_name)

