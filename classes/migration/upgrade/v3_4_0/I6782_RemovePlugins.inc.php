<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6782_RemovePlugins.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6782_RemovePlugins
 * @brief Remove the usageStats and views report plugin.
 *
 * This script has to be called after I6782_Metrics, i.e. after usageStats plugin settings were successfully migrated.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\file\FileManager;

class I6782_RemovePlugins extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        $fileManager = new FileManager();

        // Remove usageStats plugin
        $category = 'generic';
        $productName = 'usageStats';
        $pluginVersionsEntryExists = DB::table('versions')
            ->where('current', 1)
            ->where('product_type', '=', 'plugins.' . $category)
            ->where('product', '=', $productName)
            ->exists();
        if ($pluginVersionsEntryExists) {
            $pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            $pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            // Delete files
            // Can we check permissions in the perflight spript?
            $fileManager->rmtree($pluginDest);
            $fileManager->rmtree($pluginLibDest);
            if (is_dir($pluginDest) || is_dir($pluginLibDest)) {
                $this->_installer->log("Plugin \"plugins.{$category}.{$productName}\" could not be deleted from the file system. This may be a permissions problem. Please make sure that the web server is able to write to the plugins directory (including subdirectories) but don't forget to secure it again later.");
            } else {
                // Differently to versionDao->disableVersion, we will remove the entry from the table 'versions' and plugin_settings
                // becase the plugin cannot be used any more
                DB::table('versions')
                    ->where('product_type', '=', 'plugins.' . $category)
                    ->where('product', '=', $productName)
                    ->delete();
                DB::table('plugin_settings')
                    ->where('plugin_name', '=', 'usagestatsplugin')
                    ->delete();
                // Do we need to do anything with PluginRegistry?
            }
        }

        // Remove views report plugin
        $category = 'reports';
        $productName = 'views';
        $pluginVersionsEntryExists = DB::table('versions')
            ->where('product_type', '=', 'plugins.' . $category)
            ->where('product', '=', $productName)
            ->exists();
        if ($pluginVersionsEntryExists) {
            $pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            $pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . PKP_LIB_PATH . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $productName;
            // Delete files
            // Can we check permissions in the perflight spript?
            $fileManager->rmtree($pluginDest);
            $fileManager->rmtree($pluginLibDest);
            if (is_dir($pluginDest) || is_dir($pluginLibDest)) {
                $this->_installer->log("Plugin \"plugins.{$category}.{$productName}\" could not be deleted from the file system. This may be a permissions problem. Please make sure that the web server is able to write to the plugins directory (including subdirectories) but don't forget to secure it again later.");
            } else {
                // Differently to versionDao->disableVersion, we will remove the entry from the table 'versions'
                // becase the plugin cannot be used any more.
                // There were no entries in plugin_settings for the views report plugin.
                DB::table('versions')
                    ->where('product_type', '=', 'plugins.' . $category)
                    ->where('product', '=', $productName)
                    ->delete();
                // Do we need to do anything with PluginRegistry?
            }
        }

        // Remove usageStats plugin scheduled task from the Acron plugin
        // The setting 'crontab' is site-wide
        $crontab = DB::table('plugin_settings')->where('plugin_name', '=', 'acronplugin')->where('setting_name', '=', 'crontab')->value('setting_value');
        $tasks = json_decode($crontab, true);
        foreach ((array) $tasks as $index => $task) {
            if ($task['className'] == 'plugins.generic.usageStats.UsageStatsLoader') {
                unset($tasks[$index]);
            }
        }
        $newCronTab = json_encode(array_values($tasks), JSON_UNESCAPED_UNICODE);
        DB::table('plugin_settings')->where('plugin_name', '=', 'acronplugin')->where('setting_name', '=', 'crontab')->update(['setting_value' => $newCronTab]);
    }

    /**
     * Reverse the downgrades
     */
    public function down(): void
    {
        // We don't have the data to downgrade and downgrades are unwanted here anyway.
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I6782_RemovePlugins', '\I6782_RemovePlugins');
}
