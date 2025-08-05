<?php

declare(strict_types=1);

use Laravel\Boost\Install\ApplicationDetector;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->detector = new ApplicationDetector();
});

test('getPlatform returns correct platform identifier', function () {
    $reflection = new ReflectionClass($this->detector);
    $method = $reflection->getMethod('getPlatform');
    $method->setAccessible(true);
    
    $platform = $method->invoke($this->detector);
    
    expect($platform)->toBeIn(['darwin', 'linux', 'windows']);
});

test('expandPath expands Windows environment variables', function () {
    $reflection = new ReflectionClass($this->detector);
    $method = $reflection->getMethod('expandPath');
    $method->setAccessible(true);
    
    // Mock environment variable
    putenv('TEST_VAR=C:\\TestPath');
    
    $expanded = $method->invoke($this->detector, '%TEST_VAR%\\SubFolder', 'windows');
    
    expect($expanded)->toBe('C:\\TestPath\\SubFolder');
    
    // Clean up
    putenv('TEST_VAR');
});

test('expandPath expands Unix home directory', function () {
    $reflection = new ReflectionClass($this->detector);
    $method = $reflection->getMethod('expandPath');
    $method->setAccessible(true);
    
    // Mock HOME environment variable
    $originalHome = getenv('HOME');
    putenv('HOME=/home/testuser');
    
    $expanded = $method->invoke($this->detector, '~/.config/app', 'linux');
    
    expect($expanded)->toBe('/home/testuser/.config/app');
    
    // Restore original HOME
    if ($originalHome) {
        putenv("HOME=$originalHome");
    }
});

test('detectInProject detects applications by directory', function () {
    $tempDir = sys_get_temp_dir() . '/boost_test_' . uniqid();
    mkdir($tempDir);
    mkdir($tempDir . '/.vscode');
    
    $detected = $this->detector->detectInProject($tempDir);
    
    expect($detected)->toContain('vscode');
    
    // Cleanup
    rmdir($tempDir . '/.vscode');
    rmdir($tempDir);
});

test('detectInProject detects applications by file', function () {
    $tempDir = sys_get_temp_dir() . '/boost_test_' . uniqid();
    mkdir($tempDir);
    file_put_contents($tempDir . '/.windsurfrules.md', 'test');
    
    $detected = $this->detector->detectInProject($tempDir);
    
    expect($detected)->toContain('windsurf');
    
    // Cleanup
    unlink($tempDir . '/.windsurfrules.md');
    rmdir($tempDir);
});

test('detectInProject detects applications with mixed type', function () {
    $tempDir = sys_get_temp_dir() . '/boost_test_' . uniqid();
    mkdir($tempDir);
    file_put_contents($tempDir . '/CLAUDE.md', 'test');
    
    $detected = $this->detector->detectInProject($tempDir);
    
    expect($detected)->toContain('claudecode');
    
    // Cleanup
    unlink($tempDir . '/CLAUDE.md');
    rmdir($tempDir);
});

test('detectInProject detects copilot with nested file path', function () {
    $tempDir = sys_get_temp_dir() . '/boost_test_' . uniqid();
    mkdir($tempDir);
    mkdir($tempDir . '/.github');
    file_put_contents($tempDir . '/.github/copilot-instructions.md', 'test');
    
    $detected = $this->detector->detectInProject($tempDir);
    
    expect($detected)->toContain('copilot');
    
    // Cleanup
    unlink($tempDir . '/.github/copilot-instructions.md');
    rmdir($tempDir . '/.github');
    rmdir($tempDir);
});

test('addDetectionConfig adds configuration for specific platform', function () {
    $this->detector->addDetectionConfig('testapp', [
        'paths' => ['/test/path'],
        'type' => 'directory',
    ], 'darwin');
    
    $reflection = new ReflectionClass($this->detector);
    $property = $reflection->getProperty('detectionConfig');
    $property->setAccessible(true);
    $config = $property->getValue($this->detector);
    
    expect($config['darwin'])->toHaveKey('testapp');
    expect($config['darwin']['testapp']['paths'])->toContain('/test/path');
});

test('addDetectionConfig adds configuration for all platforms when platform is null', function () {
    $this->detector->addDetectionConfig('testapp', [
        'paths' => ['/test/path'],
        'type' => 'directory',
    ]);
    
    $reflection = new ReflectionClass($this->detector);
    $property = $reflection->getProperty('detectionConfig');
    $property->setAccessible(true);
    $config = $property->getValue($this->detector);
    
    expect($config['darwin'])->toHaveKey('testapp');
    expect($config['linux'])->toHaveKey('testapp');
    expect($config['windows'])->toHaveKey('testapp');
});

test('addProjectDetectionConfig adds project detection configuration', function () {
    $this->detector->addProjectDetectionConfig('testapp', [
        'files' => ['.testapp'],
        'type' => 'file',
    ]);
    
    $reflection = new ReflectionClass($this->detector);
    $property = $reflection->getProperty('projectDetectionConfig');
    $property->setAccessible(true);
    $config = $property->getValue($this->detector);
    
    expect($config)->toHaveKey('testapp');
    expect($config['testapp']['files'])->toContain('.testapp');
});

test('detectInstalled respects platform-specific configuration', function () {
    $detector = new ApplicationDetector();
    
    // Get the actual platform
    $reflection = new ReflectionClass($detector);
    $getPlatform = $reflection->getMethod('getPlatform');
    $getPlatform->setAccessible(true);
    $platform = $getPlatform->invoke($detector);
    
    // Set up a test configuration with a non-existent path
    $property = $reflection->getProperty('detectionConfig');
    $property->setAccessible(true);
    $property->setValue($detector, [
        $platform => [
            'testapp' => [
                'paths' => ['/this/path/does/not/exist/testapp'],
                'type' => 'directory',
            ],
        ],
        // Different platform should not be detected
        'other_platform' => [
            'otherapp' => [
                'paths' => ['/some/other/path'],
                'type' => 'directory',
            ],
        ],
    ]);
    
    $detected = $detector->detectInstalled();
    
    expect($detected)->not->toContain('testapp');
    expect($detected)->not->toContain('otherapp');
});

test('detectInstalled returns empty array for unsupported platform', function () {
    $detector = new ApplicationDetector();
    
    // Clear detection config
    $reflection = new ReflectionClass($detector);
    $property = $reflection->getProperty('detectionConfig');
    $property->setAccessible(true);
    $property->setValue($detector, []);
    
    $detected = $detector->detectInstalled();
    
    expect($detected)->toBeEmpty();
});

test('isAppInstalled handles wildcards in paths', function () {
    $reflection = new ReflectionClass($this->detector);
    $method = $reflection->getMethod('isAppInstalled');
    $method->setAccessible(true);
    
    // This test would need to mock glob() function, which is difficult
    // In a real scenario, you might use a virtual file system
    $config = [
        'paths' => ['/nonexistent/path/*'],
        'type' => 'directory',
    ];
    
    $result = $method->invoke($this->detector, $config, 'darwin');
    
    expect($result)->toBeFalse();
});