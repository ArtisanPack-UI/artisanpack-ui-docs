<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create( 'changelogs', function ( Blueprint $table ) {
			$table->id();
			$table->text( 'content' );
			$table->foreignId( 'package_id' )->constrained( 'packages' );
			$table->timestamps();
		} );
	}

	public function down(): void
	{
		Schema::dropIfExists( 'changelogs' );
	}
};
