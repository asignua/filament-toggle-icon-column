<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Feature;

use Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn;
use Asignua\FilamentToggleIconColumn\Tests\Fixtures\DenyUpdateUserPolicy;
use Asignua\FilamentToggleIconColumn\Tests\Fixtures\ReviewTable;
use Asignua\FilamentToggleIconColumn\Tests\Fixtures\ToggleOnlyTaskPolicy;
use Asignua\FilamentToggleIconColumn\Tests\Fixtures\UlidItem;
use Asignua\FilamentToggleIconColumn\Tests\Fixtures\ViewOnlyTaskPolicy;
use Asignua\FilamentToggleIconColumn\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Throwable;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\TasksRelationManager;
use Workbench\App\Models\Task;
use Workbench\App\Models\User;
use Workbench\App\Policies\TaskPolicy;

/**
 * Independent review tests: regression tests for the review findings (see the docblock on each test).
 */
class IndependentReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ReviewTable::$columns = null;
        ReviewTable::$query = null;
    }

    protected function tearDown(): void
    {
        ReviewTable::$columns = null;
        ReviewTable::$query = null;

        parent::tearDown();
    }

    private function task(array $attributes = []): Task
    {
        return Task::query()->create($attributes + ['title' => 'Write docs']);
    }

    // ---------------------------------------------------------------- authorization

    public function test_authorize_false_skips_the_policy(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        $task = $this->task(['is_locked' => true]);

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')->authorize(false)];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertTrue($task->fresh()->is_done);
    }

    public function test_authorize_with_a_custom_ability(): void
    {
        Gate::policy(Task::class, ToggleOnlyTaskPolicy::class);
        $open = $this->task();
        $locked = $this->task(['is_locked' => true]);

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')->authorize('toggle')];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'is_done', (string) $open->getKey(), true)
            ->call('updateTableColumnState', 'is_done', (string) $locked->getKey(), true);

        $this->assertTrue($open->fresh()->is_done);
        $this->assertFalse($locked->fresh()->is_done);
    }

    public function test_authorize_closure_receives_the_record(): void
    {
        Gate::policy(Task::class, ToggleOnlyTaskPolicy::class);
        $task = $this->task();

        ReviewTable::$columns = fn (): array => [
            ToggleIconColumn::make('is_done')->authorize(fn (Task $record): string => $record->title === 'Write docs' ? 'toggle' : 'update'),
        ];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertTrue($task->fresh()->is_done);
    }

    public function test_a_guest_cannot_toggle_a_record_with_a_policy(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        $task = $this->task();

        $this->app['auth']->guard()->logout();

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')];

        Livewire::test(ReviewTable::class)
            ->assertSeeHtml('aria-disabled="true"')
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertFalse($task->fresh()->is_done);
    }

    public function test_relation_manager_refuses_a_record_of_another_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $foreign = $this->task(['user_id' => $stranger->getKey()]);

        Livewire::test(TasksRelationManager::class, ['ownerRecord' => $owner, 'pageClass' => EditUser::class])
            ->call('updateTableColumnState', 'is_done', (string) $foreign->getKey(), true);

        $this->assertFalse($foreign->fresh()->is_done);
    }

    public function test_a_column_name_that_is_not_in_the_table_is_ignored(): void
    {
        $task = $this->task();

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'is_locked', (string) $task->getKey(), true);

        $this->assertFalse($task->fresh()->is_locked);
    }

    public function test_a_hidden_column_does_not_write(): void
    {
        $task = $this->task();

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')->hidden()];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertFalse($task->fresh()->is_done);
    }

    /**
     * FINDING: for a relationship column (`user.is_active`) the policy is asked about the ROW
     * record (Task), but the write goes to the RELATED record (User). A user who may update the
     * task but not the user flips the user's flag.
     */
    public function test_relationship_column_checks_the_policy_of_the_record_it_writes(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, DenyUpdateUserPolicy::class);

        $owner = User::factory()->create(['is_active' => true]);
        $task = $this->task(['user_id' => $owner->getKey()]);

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('user.is_active')];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'user.is_active', (string) $task->getKey(), false);

        $this->assertTrue($owner->fresh()->is_active, 'The related User was updated although its policy denies `update`.');
    }

    /**
     * FINDING: the column ignores the panel's strict authorization mode. With
     * `->strictAuthorization()` Filament treats a model without a policy as a configuration
     * error; the column silently treats it as "allowed" and writes.
     */
    public function test_strict_authorization_mode_is_honoured(): void
    {
        Filament::getPanel('admin')->strictAuthorization();
        // Task has an auto-discovered policy in the workbench; User has none.
        $user = User::factory()->create(['is_active' => false]);

        ReviewTable::$query = static fn () => User::query()->whereKey($user->getKey());
        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_active')];

        try {
            Livewire::test(ReviewTable::class)
                ->call('updateTableColumnState', 'is_active', (string) $user->getKey(), true);
        } catch (Throwable) {
            // A LogicException (Filament's own behaviour) is an acceptable outcome.
        } finally {
            Filament::getPanel('admin')->strictAuthorization(false);
            ReviewTable::$query = null;
        }

        $this->assertFalse($user->fresh()->is_active, 'Strict authorization: a model without a policy was written.');
    }

    /**
     * Same semantics as Filament resources: a policy that has no `update` method allows the write
     * (the column delegates to `get_authorization_response()`).
     */
    public function test_a_policy_without_the_ability_allows_as_in_filament_resources(): void
    {
        Gate::policy(Task::class, ViewOnlyTaskPolicy::class);
        $task = $this->task();

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), true);

        $this->assertTrue($task->fresh()->is_done);
    }

    // ---------------------------------------------------------------- input handling

    public function test_validation_error_is_returned_to_the_client(): void
    {
        $task = $this->task();

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')->rules(['boolean', 'accepted'])];

        $response = Livewire::test(ReviewTable::class)->instance()
            ->updateTableColumnState('is_done', (string) $task->getKey(), false);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertNotSame('', $response['error']);
    }

    public function test_string_boolean_inputs_are_accepted(): void
    {
        $task = $this->task();

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')];

        Livewire::test(ReviewTable::class)
            ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), '1');

        $this->assertTrue($task->fresh()->is_done);
    }

    /**
     * FINDING: the default rules are `['boolean']` and `CanBeValidated` appends `nullable`, so a
     * crafted `updateTableColumnState(..., null)` (or `''`) passes validation and writes NULL. On a
     * NOT NULL boolean column that is an unhandled QueryException (HTTP 500); on a nullable column
     * the boolean silently becomes NULL — a third state the column cannot display.
     */
    public function test_a_null_input_neither_crashes_nor_writes(): void
    {
        $task = $this->task(['is_done' => true]);

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')];

        $exception = null;

        try {
            Livewire::test(ReviewTable::class)
                ->call('updateTableColumnState', 'is_done', (string) $task->getKey(), null);
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertNull($exception, 'Null input crashed: '.($exception?->getMessage() ?? ''));
        $this->assertTrue($task->fresh()->is_done);
    }

    // ---------------------------------------------------------------- keys

    public function test_it_toggles_a_ulid_keyed_model(): void
    {
        Schema::create('review_ulid_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        $item = UlidItem::query()->create([]);

        ReviewTable::$query = fn () => UlidItem::query();
        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_active')];

        Livewire::test(ReviewTable::class)
            ->assertSeeHtml((string) $item->getKey())
            ->call('updateTableColumnState', 'is_active', (string) $item->getKey(), true);

        $this->assertTrue($item->fresh()?->is_active);
    }

    // ---------------------------------------------------------------- rendering / XSS

    public function test_record_content_in_the_tooltip_and_label_is_not_rendered_as_html(): void
    {
        $this->task(['title' => '"><img src=x onerror=alert(1)>']);

        ReviewTable::$columns = fn (): array => [
            ToggleIconColumn::make('is_done')
                ->label('<b onmouseover=alert(2)>Done</b>')
                ->tooltip(fn (Task $record): string => $record->title),
        ];

        Livewire::test(ReviewTable::class)
            ->assertDontSeeHtml('<img src=x onerror=alert(1)>')
            ->assertDontSeeHtml('<b onmouseover=alert(2)>')
            ->assertSeeHtml('aria-label="Done"');
    }

    public function test_size_and_colors_are_applied(): void
    {
        $this->task(['is_done' => true]);

        ReviewTable::$columns = fn (): array => [
            ToggleIconColumn::make('is_done')->size(IconSize::ExtraLarge)->onColor('danger'),
        ];

        Livewire::test(ReviewTable::class)
            ->assertSeeHtml('fi-size-xl')
            ->assertSeeHtml('fi-color-danger');
    }

    public function test_a_null_state_renders_as_off(): void
    {
        $task = $this->task();

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('settings.flag')];

        Livewire::test(ReviewTable::class)->assertSeeHtml('aria-pressed="false"');

        $this->assertNull($task->settings);
    }

    /**
     * FINDING: `stateTooltip()` keeps saying "Click to disable/enable" on a cell that cannot be
     * clicked (disabled, or denied by the policy).
     */
    public function test_state_tooltip_does_not_invite_a_click_on_a_disabled_cell(): void
    {
        $this->task(['is_done' => true]);

        ReviewTable::$columns = fn (): array => [ToggleIconColumn::make('is_done')->disabled()->stateTooltip()];

        Livewire::test(ReviewTable::class)
            ->assertSeeHtml('aria-disabled="true"')
            ->assertDontSeeHtml(e(__('filament-toggle-icon-column::filament-toggle-icon-column.tooltip.on')));
    }

    // ---------------------------------------------------------------- archilex API parity

    /**
     * FINDING: archilex's `->size('xl')` (string sizes xs/sm/md/lg/xl) — and core IconColumn's
     * `size(IconSize|string)` — throw a TypeError here; migration code breaks.
     */
    public function test_size_accepts_the_archilex_string_sizes(): void
    {
        try {
            $column = ToggleIconColumn::make('is_done')->size('xl'); // @phpstan-ignore argument.type (the point of the test)
        } catch (Throwable $e) {
            $this->fail('size(\'xl\') threw '.$e::class.': '.$e->getMessage());
        }

        $this->assertSame(IconSize::ExtraLarge, $column->getSize());
    }

    /**
     * FINDING: archilex's `->hoverColor()` is not ported; existing v3 code calling it fails with
     * BadMethodCallException.
     */
    public function test_hover_color_from_archilex_is_available(): void
    {
        $this->assertTrue(method_exists(ToggleIconColumn::class, 'hoverColor'), 'ToggleIconColumn::hoverColor() is missing.');
    }

    // ---------------------------------------------------------------- performance

    public function test_rendering_more_rows_with_a_policy_adds_no_queries(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);

        ReviewTable::$columns = fn (): array => [TextColumn::make('title'), ToggleIconColumn::make('is_done')];

        $this->task();
        $one = $this->countRenderQueries();

        foreach (range(1, 9) as $i) {
            $this->task(['title' => 'Task '.$i]);
        }

        $ten = $this->countRenderQueries();

        $this->assertSame($one, $ten, 'Query count grows with the number of rows (N+1).');
    }

    private function countRenderQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(ReviewTable::class);

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }
}
