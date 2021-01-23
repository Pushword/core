<?php

use Pushword\Installer\PostInstall;

if (! file_exists('vendor')) {
    throw new Exception('installer mus be run from root');
}

// Add locale and database (used in pushword config)
PostInstall::replace('config/services.yaml', 'parameters:', 'parameters:\n    locale: \'fr\'\n    database: \'%env(resolve:DATABASE_URL)%\'');
PostInstall::replace('.env', 'DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=13&charset=utf8"', 'DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"');

// Set pushword bundle first to avoid errors
PostInstall::replace('config/bundles.php', 'Pushword\Core\PushwordCoreBundle::class => [\'all\' => true],', '');
PostInstall::replace('config/bundles.php', 'return [', 'return [\n    Pushword\Core\PushwordCoreBundle::class => [\'all\' => true],', '');

//We copy pushword config to easily edit it later manually
copy('vendor/pushword/installer/src/pushword.yaml', 'config/packages/pushword.yaml');

// Copy Demo Medias
PostInstall::mirror('vendor/pushword/skeleton/media~', 'media');

// Copy Entities
PostInstall::mirror('vendor/pushword/skeleton/src/Entity', 'src/Entity');

// Loading Puswhord Routes
PostInstall::addOnTop('config/routes.yaml', "pushword:\n    resource: '@PushwordCoreBundle/Resources/config/routes/all.yaml'");

// Create database
exec('php bin/console doctrine:schema:create -q && php bin/console doctrine:fixtures:load -q && php bin/console pushword:image:cache -q &');

// Add an admin user
//exec('php bin/console pushword:user:create admin@example.tld p@ssword ROLE_SUPER_ADMIN');

// Symlink assets
exec('php bin/console assets:install --symlink --relative -q');
mkdir('public/build');
file_put_contents('public/build/manifest.json', '{}');
unlink('package.json');
PostInstall::remove('assets');
PostInstall::mirror('vendor/pushword/skeleton/assets', 'assets');
