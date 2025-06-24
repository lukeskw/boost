<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\AiAssistant\Mcp\Tools\LogReader;
use Laravel\Mcp\Tools\ToolResponse;

it('calls process with the correct log path when one is provided', function () {
    Process::fake([
        '*' => Process::result(output: 'test output'),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();

    $tool->handle([
        'lines' => 10,
        'log_path' => 'my/custom/log.log',
    ]);

    Process::assertRan(function ($process) {
        return $process->command === ['tail', '-n', '10', base_path('my/custom/log.log')];
    });
});

it('calls process with the correct log path when an absolute path is provided', function () {
    Process::fake([
        '*' => Process::result(output: 'test output'),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $absolutePath = '/var/logs/my-app.log';
    $tool->handle([
        'lines' => 10,
        'log_path' => $absolutePath,
    ]);

    Process::assertRan(function ($process) use ($absolutePath) {
        return $process->command === ['tail', '-n', '10', $absolutePath];
    });
});

it('calls process with the default log path when none is provided', function () {
    Process::fake([
        '*' => Process::result(output: 'test output'),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $tool->handle([
        'lines' => 10,
    ]);

    Process::assertRan(function ($process) {
        return $process->command === ['tail', '-n', '10', storage_path('logs/laravel.log')];
    });
});

it('calls process with the default log path when an empty string is provided', function () {
    Process::fake([
        '*' => Process::result(output: 'test output'),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $tool->handle([
        'lines' => 10,
        'log_path' => '',
    ]);

    Process::assertRan(function ($process) {
        return $process->command === ['tail', '-n', '10', storage_path('logs/laravel.log')];
    });
});

it('calls process with grep pattern when provided', function () {
    Process::fake([
        '*' => Process::result(output: 'test output'),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $tool->handle([
        'lines' => 10,
        'grep' => 'error',
    ]);

    Process::assertRan(function ($process) {
        $logPath = escapeshellarg(storage_path('logs/laravel.log'));
        $grepPattern = escapeshellarg('error');
        $expectedCommand = "grep {$grepPattern} {$logPath} | tail -n 10";

        return $process->command === ['sh', '-c', $expectedCommand];
    });
});

it('returns an error if the log file does not exist', function () {
    File::shouldReceive('exists')->andReturn(false);

    $tool = new LogReader();
    $response = $tool->handle([
        'lines' => 10,
    ]);

    $logPath = storage_path('logs/laravel.log');
    expect($response)->toEqual(new ToolResponse("Log file not found or is not readable: {$logPath}"));
});

it('returns an error if the log file is not readable', function () {
    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(false);

    $tool = new LogReader();
    $response = $tool->handle([
        'lines' => 10,
    ]);

    $logPath = storage_path('logs/laravel.log');
    expect($response)->toEqual(new ToolResponse("Log file not found or is not readable: {$logPath}"));
});

it('returns an error if the process fails', function () {
    Process::fake([
        '*' => Process::result(
            output: '',
            errorOutput: 'Something went wrong',
            exitCode: 1
        ),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $response = $tool->handle([
        'lines' => 10,
    ]);

    expect($response)->toEqual(new ToolResponse("Failed to read log file. Error: Something went wrong"));
});

it('returns a message if no log entries match the grep pattern', function () {
    Process::fake([
        '*' => Process::result(output: ' '),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $response = $tool->handle([
        'lines' => 10,
        'grep' => 'non_existent_pattern',
    ]);

    expect($response)->toEqual(new ToolResponse("No log entries found matching pattern: non_existent_pattern"));
});

it('returns a message if the log file is empty', function () {
    Process::fake([
        '*' => Process::result(output: ''),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $response = $tool->handle([
        'lines' => 10,
    ]);

    expect($response)->toEqual(new ToolResponse('Log file is empty or no entries found.'));
});

it('returns the log content on success', function () {
    Process::fake([
        '*' => Process::result(output: " log line 1 \n log line 2 "),
    ]);

    File::shouldReceive('exists')->andReturn(true);
    File::shouldReceive('isReadable')->andReturn(true);

    $tool = new LogReader();
    $response = $tool->handle([
        'lines' => 10,
    ]);

    expect($response)->toEqual(new ToolResponse("log line 1 \n log line 2"));
});
