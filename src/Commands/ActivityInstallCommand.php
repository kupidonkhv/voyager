<?php

namespace TCG\Voyager\Commands;

use Illuminate\Console\Command;
use TCG\Voyager\Support\ActivityInstaller;

class ActivityInstallCommand extends Command
{
    protected $name = 'voyager:activity:install';

    protected $description = 'Install Voyager activity log permissions and menu item';

    public function handle(ActivityInstaller $installer): int
    {
        $result = $installer->install();

        $this->info('Activity log permissions are installed.');
        $this->line('Menu item: '.($result['menu_item'] ?: 'not created (admin menu not found)'));

        return self::SUCCESS;
    }
}
