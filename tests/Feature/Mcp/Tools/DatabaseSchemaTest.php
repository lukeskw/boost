<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\AiAssistant\Mcp\Tools\DatabaseSchema;

beforeEach(function () {
    // Switch the default connection to a file-backed SQLite DB.
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
    ]);

    // Ensure the DB file *and* schema folder exist.
    if (! is_file($file = database_path('testing.sqlite'))) {
        touch($file);
    }

    if (! is_dir($path = database_path('schema'))) {
        mkdir($path, 0777, true);
    }

    // Build a throw-away table that we expect in the dump.
    Schema::create('examples', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
});

afterEach(function () {
    $dbFile = database_path('testing.sqlite');
    if (File::exists($dbFile)) {
        File::delete($dbFile);
    }

    $schemaDir = database_path('schema');
    if (File::isDirectory($schemaDir)) {
        File::deleteDirectory($schemaDir);
    }
});

test('it dumps the schema and returns it in the tool response', function () {
    $tool = new DatabaseSchema;
    $response = $tool->handle([]);

    $sql = $response->toArray()['content'][0]['text'];

    expect($sql)->toContain(
        'CREATE TABLE IF NOT EXISTS "examples"'
    );

    $this->assertDirectoryIsReadable(database_path('schema'));
    expect(glob(database_path('schema/*.sql')))->toBeEmpty();
});
