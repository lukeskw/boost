<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Boost\Mcp\Tools\DatabaseSchema;

beforeEach(function () {
    // Switch the default connection to a file-backed SQLite DB.
    config()->set('database.default', 'testing');
    config()->set('database.connections.testing', [
        'driver' => 'sqlite',
        'database' => database_path('testing.sqlite'),
        'prefix' => '',
    ]);

    // Ensure the DB file exists
    if (! is_file($file = database_path('testing.sqlite'))) {
        touch($file);
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
});

test('it returns structured database schema', function () {
    $tool = new DatabaseSchema;
    $response = $tool->handle([]);

    $responseArray = $response->toArray();
    expect($responseArray['isError'])->toBeFalse();

    $schemaArray = json_decode($responseArray['content'][0]['text'], true);

    expect($schemaArray)->toHaveKey('engine');
    expect($schemaArray['engine'])->toBe('sqlite');

    expect($schemaArray)->toHaveKey('tables');
    expect($schemaArray['tables'])->toHaveKey('examples');

    $exampleTable = $schemaArray['tables']['examples'];
    expect($exampleTable)->toHaveKey('columns');
    expect($exampleTable['columns'])->toHaveKey('id');
    expect($exampleTable['columns'])->toHaveKey('name');

    expect($exampleTable['columns']['id']['type'])->toBe('integer');
    expect($exampleTable['columns']['name']['type'])->toBe('varchar');

    expect($exampleTable)->toHaveKey('indexes');
    expect($exampleTable)->toHaveKey('foreign_keys');
    expect($exampleTable)->toHaveKey('triggers');
    expect($exampleTable)->toHaveKey('check_constraints');

    expect($schemaArray)->toHaveKey('global');
    expect($schemaArray['global'])->toHaveKey('views');
    expect($schemaArray['global'])->toHaveKey('stored_procedures');
    expect($schemaArray['global'])->toHaveKey('functions');
    expect($schemaArray['global'])->toHaveKey('sequences');
});

test('it filters tables by name', function () {
    // Create another table
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('email');
    });

    $tool = new DatabaseSchema;

    // Test filtering for 'example'
    $response = $tool->handle(['filter' => 'example']);
    $responseArray = $response->toArray();
    $schemaArray = json_decode($responseArray['content'][0]['text'], true);

    expect($schemaArray['tables'])->toHaveKey('examples');
    expect($schemaArray['tables'])->not->toHaveKey('users');

    // Test filtering for 'user'
    $response = $tool->handle(['filter' => 'user']);
    $responseArray = $response->toArray();
    $schemaArray = json_decode($responseArray['content'][0]['text'], true);

    expect($schemaArray['tables'])->toHaveKey('users');
    expect($schemaArray['tables'])->not->toHaveKey('examples');
});
