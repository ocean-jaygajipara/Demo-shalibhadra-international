<?php

namespace App\Console\Commands;

use App\Models\SubMenu;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class GenerateMenuMapConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:menu-map';
    protected $description = 'Generate config/menu_map.php from sub_menus table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $map = [];

        $subMenus = SubMenu::whereNotNull('route_name')->get();

        foreach ($subMenus as $sub) {
            $sub->name = str_replace(' ', '', $sub->name);
            $map[$sub->name] = [
                'main_menu_id' => $sub->main_menu_id,
                'sub_menu_id' => $sub->id,
            ];
        }

        $configContent = "<?php\n\nreturn " . var_export($map, true) . ";\n";
        File::put(config_path('menu_map.php'), $configContent);

        $this->info('✅ menu_map.php generated successfully!');
    }
}
