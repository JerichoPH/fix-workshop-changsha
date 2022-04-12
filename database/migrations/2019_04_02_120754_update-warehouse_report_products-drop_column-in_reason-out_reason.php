<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWarehouseReportProductsDropColumnInReasonOutReason extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_report_products', function (Blueprint $table) {
            $table->dropColumn('in_reason');
            $table->dropColumn('out_reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_report_products', function (Blueprint $table) {
            //
        });
    }
}
