<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatchUuidColumnToActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                // MariaDB ไม่รองรับ type 'uuid' จึงเปลี่ยนเป็น CHAR(36)
                if (!Schema::hasColumn(config('activitylog.table_name'), 'batch_uuid')) {
                    $table->char('batch_uuid', 36)->nullable()->after('properties');
                }
            });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                if (Schema::hasColumn(config('activitylog.table_name'), 'batch_uuid')) {
                    $table->dropColumn('batch_uuid');
                }
            });
    }
}
