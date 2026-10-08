<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Feature;

use Asignua\FilamentToggleIconColumn\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\Tasks\Pages\ListTasks;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\TasksRelationManager;
use Workbench\App\Livewire\HookedTasks;
use Workbench\App\Models\Task;
use Workbench\App\Models\User;
use Workbench\App\Policies\TaskPolicy;

class ToggleIconColumnTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        HookedTasks::$log = [];
    }

    private function task(array $attributes = []): Task
    {
        return Task::query()->create($attributes + ['title' => 'Write docs']);
    }

    public function test_it_renders_an_accessible_icon_button(): void
    {
        $task = $this->task(['is_done' => true]);

        Livewire::test(ListTasks::class)
            ->assertCanSeeTableRecords([$task])
            ->assertSeeHtml('role="button"')
            ->assertSeeHtml('aria-pressed="true"')
            ->assertSeeHtml('aria-label="Done"')
            ->assertSeeHtml('x-on:keydown.space.prevent.stop')
            ->assertSeeHtml('x-on:keydown.enter.prevent.stop')
            ->assertSeeHtml('fi-ta-toggle-icon')
            ->assertSeeHtml('<svg');
    }

    public function test_it_renders_pressed_false_for_an_off_record(): void
    {
        $this->task(['is_done' => false]);

        Livewire::test(ListTasks::class)->assertSeeHtml('aria-pressed="false"');
    }

    public function test_clicking_flips_the_attribute_both_ways(): void
    {
        $task = $this->task();

        Livewire::test(ListTasks::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertTrue($task->fresh()->is_done);

        Livewire::test(ListTasks::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), false);

        $this->assertFalse($task->fresh()->is_done);
    }

    public function test_a_disabled_column_does_not_write(): void
    {
        $task = $this->task(['title' => 'frozen']);

        Livewire::test(ListTasks::class)
            ->assertSeeHtml('aria-disabled="true"')
            ->call('updateTableColumnState', 'is_locked', (string) $task->getKey(), true);

        $this->assertFalse($task->fresh()->is_locked);
    }

    public function test_validation_rules_reject_the_input_and_return_the_error(): void
    {
        $task = $this->task(['is_locked' => true]);

        // `is_locked` carries ['boolean', 'accepted']: turning it off must fail.
        Livewire::test(ListTasks::class)
            ->call('updateTableColumnState', 'is_locked', (string) $task->getKey(), false);

        $this->assertTrue($task->fresh()->is_locked);
    }

    public function test_the_default_boolean_rule_rejects_garbage(): void
    {
        $task = $this->task();

        Livewire::test(ListTasks::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), 'maybe');

        $this->assertFalse($task->fresh()->is_done);
    }

    public function test_a_record_the_policy_forbids_to_update_is_not_changed(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);

        $locked = $this->task(['is_locked' => true]);
        $open = $this->task(['title' => 'Open']);

        Livewire::test(ListTasks::class)
            ->call('updateTableColumnState', 'is_done', (string) $locked->getKey(), true)
            ->call('updateTableColumnState', 'is_done', (string) $open->getKey(), true);

        $this->assertFalse($locked->fresh()->is_done);
        $this->assertTrue($open->fresh()->is_done);
    }

    public function test_a_forbidden_record_renders_disabled(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);

        $this->task(['is_locked' => true]);

        Livewire::test(ListTasks::class)->assertSeeHtml('aria-disabled="true"');
    }

    public function test_a_model_without_a_policy_is_editable(): void
    {
        $task = $this->task();

        Livewire::test(ListTasks::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertTrue($task->fresh()->is_done);
    }

    public function test_hooks_run_around_the_write_in_order(): void
    {
        $task = $this->task();

        Livewire::test(HookedTasks::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertSame(['before:false->true', 'after:true'], HookedTasks::$log);
    }

    public function test_update_state_using_replaces_the_default_write(): void
    {
        $task = $this->task();

        Livewire::test(HookedTasks::class)
            ->call('updateTableColumnState', 'is_locked', (string) $task->getKey(), true);

        $this->assertSame(['custom:true'], HookedTasks::$log);
        $this->assertFalse($task->fresh()->is_locked);
    }

    public function test_it_writes_into_a_json_attribute(): void
    {
        $task = $this->task();

        Livewire::test(HookedTasks::class)
            ->call('updateTableColumnState', 'settings.flag', (string) $task->getKey(), true);

        $this->assertTrue($task->fresh()->settings['flag']);
    }

    public function test_hover_hint_can_be_switched_off_and_size_and_alignment_apply(): void
    {
        $this->task();

        Livewire::test(HookedTasks::class)
            ->assertSeeHtml('hint: true')
            ->assertSeeHtml('hint: false')
            ->assertSeeHtml('fi-align-center');
    }

    public function test_the_state_tooltip_uses_the_translations(): void
    {
        $this->task();

        Livewire::test(HookedTasks::class)
            ->assertSee(__('filament-toggle-icon-column::filament-toggle-icon-column.tooltip.off'), escape: false);
    }

    public function test_it_works_inside_a_relation_manager(): void
    {
        $user = User::factory()->create();
        $task = $this->task(['user_id' => $user->getKey()]);

        Livewire::test(TasksRelationManager::class, ['ownerRecord' => $user, 'pageClass' => EditUser::class])
            ->assertCanSeeTableRecords([$task])
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertTrue($task->fresh()->is_done);
    }

    public function test_a_hidden_record_key_that_does_not_exist_is_ignored(): void
    {
        $task = $this->task();

        Livewire::test(ListTasks::class)
            ->call('updateTableColumnState', 'is_done', '99999', true);

        $this->assertFalse($task->fresh()->is_done);
    }
}
