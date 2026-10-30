<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Support\GalleryInstaller;

class GalleryInstallCommand extends Command
{
    protected $name = 'voyager:gallery:install';

    protected $description = 'Install the Voyager Gallery BREAD, permissions and admin menu item';

    public function handle(GalleryInstaller $installer): int
    {
        $this->ensureMediaLibrary();

        $result = $installer->install();

        if (!$result['enabled']) {
            $this->warn('Voyager Gallery is disabled in config/voyager.php.');

            return self::SUCCESS;
        }

        $this->info("Gallery BREAD is ready (data type #{$result['data_type']}).");

        if ($result['menu_item']) {
            $this->info("Gallery menu item is ready (menu item #{$result['menu_item']}).");
        } else {
            $this->warn('The admin menu was not found. Create it in the Menu Builder and run this command again.');
        }

        return self::SUCCESS;
    }

    protected function ensureMediaLibrary(): void
    {
        if (Schema::hasTable('media')) {
            return;
        }

        $this->info('Migrating the media library tables');

        $this->call('migrate', ['--force' => true]);
    }
}
