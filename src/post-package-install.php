<?php

if (! file_exists('vendor')) {
    throw new Exception('installer mus be run from root');
}

echo '~~ Add locale and database (used in pushword config)'.chr(10);
\Pushword\Installer\PostInstall::replace('config/services.yaml', 'parameters:',  "parameters:\n    locale: 'fr'\n    database: '%env(resolve:DATABASE_URL)%'");
\Pushword\Installer\PostInstall::replace('.env', 'DATABASE_URL="postgresql://db_user:db_password@127.0.0.1:5432/db_name?serverVersion=13&charset=utf8"', 'DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"');

// Set pushword bundle first to avoid errors
\Pushword\Installer\PostInstall::replace('config/bundles.php', "Pushword\Core\PushwordCoreBundle::class => ['all' => true],", '');
\Pushword\Installer\PostInstall::replace('config/bundles.php', "return [", "return [\n    Pushword\Core\PushwordCoreBundle::class => ['all' => true],");

echo '~~ Copying pushword config in config/packages to easily edit it later'.chr(10);
copy('vendor/pushword/installer/src/pushword.yaml', 'config/packages/pushword.yaml');

echo '~~ Copy Entities'.chr(10);
\Pushword\Installer\PostInstall::mirror('vendor/pushword/skeleton/src/Entity', 'src/Entity');

echo '~~ Adding Puswhord Routes'.chr(10);
\Pushword\Installer\PostInstall::addOnTop('config/routes.yaml', "pushword:\n    resource: '@PushwordCoreBundle/Resources/config/routes/all.yaml'");

echo '~~ Create database'.chr(10);
\Pushword\Installer\PostInstall::mirror('vendor/pushword/skeleton/media~', 'media');
exec('php bin/console doctrine:schema:create -q && php bin/console doctrine:fixtures:load -q && php bin/console pushword:image:cache -q &');

// Add an admin user
//exec('php bin/console pushword:user:create admin@example.tld p@ssword ROLE_SUPER_ADMIN');

echo '~~ Symlinking assets'.chr(10);
exec('php bin/console assets:install --symlink --relative -q');
\Pushword\Installer\PostInstall::dumpFile('public/build/manifest.json', '{}');
