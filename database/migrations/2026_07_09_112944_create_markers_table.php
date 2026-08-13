public function up()
{
    Schema::create('markers', function (Blueprint $table) {
        $table->id();
        $table->decimal('lat', 10, 7);
        $table->decimal('lng', 10, 7);
        $table->timestamps();
    });
}
