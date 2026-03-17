# This file is licensed under the Affero General Public License version 3 or
# later. See the LICENSE file.

app_name=nextcloud-swarm-plugin
app_id=files_external_ethswarm
build_directory=$(CURDIR)/build
temp_build_directory=$(build_directory)/temp
build_tools_directory=$(CURDIR)/build/tools
cert_directory=$(HOME)/.nextcloud/certificates

all: dev-setup lint build-js-production

release: composer pnpm-init build-js-production build-tarball

appstore: composer pnpm-init build-js-production build-appstore-tarball

dev-setup: clean-dev composer pnpm-init

lint: lint-js-check format-check php-cs

lint-fix: lint-js-fix format-fix php-cs-fix

# Dependencies
composer:
	composer install --prefer-dist

composer-update:
	composer update --prefer-dist

pnpm-init:
	pnpm install --frozen-lockfile

pnpm-update:
	pnpm update

# Building
build-js:
	pnpm run dev

build-js-production:
	pnpm run build

watch-js:
	pnpm run dev

serve-js:
	pnpm run serve

# Linting
lint-js-check:
	pnpm run lint:check

lint-js-fix:
	pnpm run lint

format-check:
	pnpm run format:check

format-fix:
	pnpm run format

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
	--exclude="./src" \
	--exclude="styles" \
	--exclude="./vendor" \
	--exclude=".oxlintrc.json" \
	--exclude="biome.json" \
	--exclude=".editorconfig" \
	--exclude=".gitignore" \
	--exclude=".php_cs.cache" \
	--exclude=".php-cs-fixer.dist.php" \
	--exclude="composer.json" \
	--exclude="composer.lock" \
	--exclude="docker-compose.yml" \
	--exclude="jsconfig.json" \
	--exclude="Makefile" \
	--exclude="package.json" \
	--exclude="pnpm-lock.yaml" \
	--exclude="vite.config.mjs" \
	--exclude="CHANGELOG.md" \
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
	--exclude="./src" \
	--exclude="styles" \
	--exclude="./vendor" \
	--exclude=".oxlintrc.json" \
	--exclude="biome.json" \
	--exclude=".editorconfig" \
	--exclude=".gitignore" \
	--exclude=".php_cs.cache" \
	--exclude=".php-cs-fixer.dist.php" \
	--exclude="composer.json" \
	--exclude="composer.lock" \
	--exclude="docker-compose.yml" \
	--exclude="jsconfig.json" \
	--exclude="Makefile" \
	--exclude="package.json" \
	--exclude="pnpm-lock.yaml" \
	--exclude="vite.config.mjs" \
	--exclude="CHANGELOG.md" \
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
