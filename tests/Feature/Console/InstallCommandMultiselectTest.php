<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Tests\TestCase;

class InstallCommandMultiselectTest extends TestCase
{
    /**
     * Test that multiselect returns keys when given an associative array.
     */
    public function test_multiselect_returns_keys_for_associative_array(): void
    {
        // Mock the prompt to simulate user selecting options
        // Note: mcp_server is already selected by default, so we don't toggle it
        Prompt::fake([
            Key::DOWN,       // Move to second option (ai_guidelines)
            Key::SPACE,      // Select second option
            Key::ENTER,      // Submit
        ]);

        $result = \Laravel\Prompts\multiselect(
            label: 'What shall we install?',
            options: [
                'mcp_server' => 'Boost MCP Server',
                'ai_guidelines' => 'Package AI Guidelines',
                'style_guidelines' => 'Laravel Style AI Guidelines',
            ],
            default: ['mcp_server']
        );

        // Assert that we get the keys, not the values
        $this->assertIsArray($result);
        $this->assertCount(2, $result, 'Should have 2 items selected');
        $this->assertContains('mcp_server', $result);
        $this->assertContains('ai_guidelines', $result);
        $this->assertNotContains('Boost MCP Server', $result);
        $this->assertNotContains('Package AI Guidelines', $result);
    }

    /**
     * Test multiselect with numeric indexed array returns values.
     */
    public function test_multiselect_returns_values_for_indexed_array(): void
    {
        Prompt::fake([
            Key::SPACE,      // Select first option
            Key::DOWN,       // Move to second option
            Key::SPACE,      // Select second option
            Key::ENTER,      // Submit
        ]);

        $result = \Laravel\Prompts\multiselect(
            label: 'Select options',
            options: ['Option 1', 'Option 2', 'Option 3'],
            default: []
        );

        // For indexed arrays, it returns the actual values
        $this->assertIsArray($result);
        $this->assertContains('Option 1', $result);
        $this->assertContains('Option 2', $result);
    }

    /**
     * Test that demonstrates multiselect behavior is consistent with InstallCommand expectations.
     * This ensures Laravel 10/11 compatibility.
     */
    public function test_multiselect_behavior_matches_install_command_expectations(): void
    {
        // Test the exact same structure used in InstallCommand::selectBoostFeatures()
        // Note: mcp_server and ai_guidelines are already selected by default
        Prompt::fake([
            Key::DOWN,       // Move to ai_guidelines (already selected)
            Key::DOWN,       // Move to style_guidelines
            Key::SPACE,      // Select style_guidelines
            Key::ENTER,      // Submit
        ]);

        $toInstallOptions = [
            'mcp_server' => 'Boost MCP Server',
            'ai_guidelines' => 'Package AI Guidelines (i.e. Framework, Inertia, Pest)',
            'style_guidelines' => 'Laravel Style AI Guidelines',
        ];

        $result = \Laravel\Prompts\multiselect(
            label: 'What shall we install?',
            options: $toInstallOptions,
            default: ['mcp_server', 'ai_guidelines'],
            required: true,
            hint: 'Style guidelines are best for new projects',
        );

        // Verify we get keys that can be used with in_array checks
        $this->assertIsArray($result);
        $this->assertCount(3, $result); // All 3 selected (2 default + 1 added)

        // These are the exact checks used in InstallCommand
        $this->assertTrue(in_array('mcp_server', $result, true));
        $this->assertTrue(in_array('ai_guidelines', $result, true));
        $this->assertTrue(in_array('style_guidelines', $result, true));

        // Verify it doesn't contain the display values
        $this->assertFalse(in_array('Boost MCP Server', $result, true));
        $this->assertFalse(in_array('Package AI Guidelines (i.e. Framework, Inertia, Pest)', $result, true));
    }
}
