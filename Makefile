# This file is licensed under the Affero General Public License version 3 or
# later. See the LICENSE file.

app_name=nextcloud-swarm-plugin
app_id=files_external_ethswarm
build_directory=$(CURDIR)/build
temp_build_directory=$(build_directory)/temp
build_tools_directory=$(CURDIR)/build/tools
cert_directory=$(HOME)/.nextcloud/certificates

all: dev-setup lint build-js-production

release: npm-init build-js-production build-tarball

appstore: npm-init build-js-production build-appstore-tarball

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

# Building
build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

serve-js:
	npm run serve

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
	--exclude="assets" \
	--exclude="build" \
	--exclude="cli" \
	--exclude="dev-environment" \
	--exclude="docker" \
	--exclude="node_modules" \
	--exclude="src" \
	--exclude="vendor" \
	--exclude=".editorconfig" \
	--exclude=".eslintrc.js" \
	--exclude=".gitignore" \
	--exclude=".php_cs.cache" \
	--exclude=".php-cs-fixer.dist.php" \
	--exclude=".prettierignore" \
	--exclude=".prettierrc.json" \
	--exclude="babel.config.js" \
	--exclude="composer.json" \
	--exclude="composer.lock" \
	--exclude="docker-compose.yml" \
	--exclude="Makefile" \
	--exclude="package-lock.json" \
	--exclude="package.json" \
	--exclude="stylelint.config.js" \
	--exclude="webpack.config.js" \
	../$(app_name)/ $(temp_build_directory)/$(app_id)
	tar czf $(build_directory)/$(app_name).tar.gz \
		-C $(temp_build_directory) $(app_id)

build-appstore-tarball:
	rm -rf $(build_directory)
	mkdir -p $(temp_build_directory)
	rsync -a \
	--exclude=".git" \
	--exclude=".github" \
	--exclude=".vscode" \
	--exclude="assets" \
	--exclude="build" \
	--exclude="cli" \
	--exclude="dev-environment" \
	--exclude="docker" \
	--exclude="node_modules" \
	--exclude="src" \
	--exclude="vendor" \
	--exclude=".editorconfig" \
	--exclude=".eslintrc.js" \
	--exclude=".gitignore" \
	--exclude=".php_cs.cache" \
	--exclude=".php-cs-fixer.dist.php" \
	--exclude=".prettierignore" \
	--exclude=".prettierrc.json" \
	--exclude="babel.config.js" \
	--exclude="composer.json" \
	--exclude="composer.lock" \
	--exclude="docker-compose.yml" \
	--exclude="Makefile" \
	--exclude="package-lock.json" \
	--exclude="package.json" \
	--exclude="stylelint.config.js" \
	--exclude="webpack.config.js" \
	../$(app_id)/ $(temp_build_directory)/$(app_id)
	@if [ -f $(cert_directory)/$(app_id).key ]; then \
		echo "Signing app files…"; \
		php ../occ integrity:sign-app \
			--privateKey=$(cert_directory)/$(app_id).key\
			--certificate=$(cert_directory)/$(app_id).crt\
			--path=$(temp_build_directory)/$(app_id); \
	fi
	tar czf $(build_directory)/$(app_id).tar.gz \
		-C $(temp_build_directory) $(app_id)
