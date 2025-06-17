<?php

test('web routes can be reached', function () {
    $response = $this->get('/ai-assistant/hello');

    $response->assertStatus(200);
    $response->assertSee('Hello World!');
});

test('api routes can be reached', function () {
    $response = $this->get('/ai-assistant/api/stats');

    $response->assertStatus(200);
    $response->assertJson([
        'message' => 'success',
    ]);
});

test('dashboard can be rendered', function () {
    $response = $this->get('/ai-assistant');

    $response->assertStatus(200);
    $response->assertSee('<h1>Laravel AI Assistant - Laravel</h1>', false);
});
