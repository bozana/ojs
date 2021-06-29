<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I6895_Institutions.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I6895_Institutions
 * @brief Migrate institution data from subscriptions into the new institution data model.
 */

namespace APP\migration\upgrade\v3_4_0;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as Schema;

class I6895_Institutions extends Migration
{
    /**
     * Run the migration.
     */
    public function up()
    {
        // Institutions.
        if (!Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->bigInteger('institution_id')->autoIncrement();
                $table->bigInteger('context_id');
                $table->string('ror', 255)->nullable();
                $table->softDeletes('deleted_at', 0);
            });
        }

        // Locale-specific institution data
        if (!Schema::hasTable('institution_settings')) {
            Schema::create('institution_settings', function (Blueprint $table) {
                $table->bigInteger('institution_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->text('setting_value')->nullable();
                $table->index(['institution_id'], 'institution_settings_institution_id');
                $table->unique(['institution_id', 'locale', 'setting_name'], 'institution_settings_pkey');
            });
        }

        // Institution IPs and IP ranges.
        if (!Schema::hasTable('institution_ip')) {
            Schema::create('institution_ip', function (Blueprint $table) {
                $table->bigInteger('institution_ip_id')->autoIncrement();
                $table->bigInteger('institution_id');
                $table->string('ip_string', 40);
                $table->bigInteger('ip_start');
                $table->bigInteger('ip_end')->nullable();
                $table->index(['institution_id'], 'institution_ip_institution_id');
                $table->index(['ip_start'], 'institution_ip_start');
                $table->index(['ip_end'], 'institution_ip_end');
            });
        }

        // Requires that institution tables are already there
        if (Schema::hasTable('institutional_subscriptions') && Schema::hasTable('institutions') && Schema::hasTable('institution_settings') && Schema::hasTable('institution_ip')) {
            if (!Schema::hasColumn('institutional_subscriptions', 'institution_id')) {

                // Add the new column institution_id to the table insitutional_substriptions
                Schema::table('institutional_subscriptions', function (Blueprint $table) {
                    $table->bigInteger('institution_id');
                });

                // pkp/pkp-lib#6895 Migrate all institutions form institutional subscriptions into new databases
                if (Schema::hasColumn('institutional_subscriptions', 'institution_name') && Schema::hasTable('institutional_subscription_ip')) {
                    $institutionalSubscriptions = DB::table('institutional_subscriptions AS i')
                        ->select('i.institutional_subscription_id', 'i.subscription_id', 'i.institution_name', 's.journal_id', 'j.primary_locale')
                        ->join('subscriptions AS s', 's.subscription_id', '=', 'i.subscription_id')
                        ->join('journals AS j', 'j.journal_id', '=', 's.journal_id')
                        ->get();

                    foreach ($institutionalSubscriptions as $institutionalSubscription) {
                        $institutionId = DB::table('institutions')->insertGetId(['journal_id' => $institutionalSubscription->journal_id]);
                        if ($institutionId) {
                            DB::table('institution_settings')->insert(['institution_id' => $institutionId, 'setting_name' => 'name', 'setting_value' => $institutionalSubscription->institution_name, 'locale' => $institutionalSubscription->primary_locale]);

                            $affected = DB::table('institutional_subscriptions')
                                ->where('institutional_subscription_id', $institutionalSubscription->institutional_subscription_id)
                                ->update(['institution_id' => $institutionId]);

                            // Get IP ranges
                            $ipRanges = DB::table('institutional_subscription_ip')
                                ->select('ip_string', 'ip_start', 'ip_end')
                                ->where('subscription_id', '=', $institutionalSubscription->subscription_id)
                                ->get();
                            foreach ($ipRanges as $ipRange) {
                                DB::table('institution_ip')->insert(['institution_id' => $institutionId, 'ip_string' => $ipRange->ip_string, 'ip_start' => $ipRange->ip_start, 'ip_end' => $ipRange->ip_end]);
                            }
                        }
                    }

                    // Drop the table institutional_subscription_ip
                    Schema::drop('institutional_subscription_ip');

                    // Drop column institution_name form institutional_subscriptions
                    Schema::table('institutional_subscriptions', function (Blueprint $table) {
                        $table->dropColumn('institution_name');
                    });
                }

                // Create the foreign key constraint (now that the values are correct and match the IDs in the parent table)
                Schema::table('institutional_subscriptions', function (Blueprint $table) {
                    $table->foreign('institution_id')->references('institution_id')->on('institutions');
                });
            }
        }
    }

    /**
     * Reverse the downgrades
     */
    public function down()
    {
        // We don't have the data to downgrade and downgrades are unwanted here anyway.
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\migration\upgrade\v3_4_0\I6895_Institutions', '\I6895_Institutions');
}
