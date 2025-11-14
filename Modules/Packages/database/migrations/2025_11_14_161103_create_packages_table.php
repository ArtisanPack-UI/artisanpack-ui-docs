<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		Schema::create( 'packages', function ( Blueprint $table ) {
			$table->id();
			$table->string( 'name' );
			$table->string( 'slug' );
			$table->integer( 'homepage' )->nullable();
			$table->string( 'wiki_url' );
			$table->string( 'changelog_url' );
			$table->timestamps();
		} );
	}

	public function down(): void
	{
		Schema::dropIfExists( 'packages' );
	}
};
