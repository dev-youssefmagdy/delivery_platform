@props(['content', 'type'])
{{--
    Header actions of a workflow content item: preview (ar / en), edit, every workflow step the current admin may run
    right now (policy `transition`), and delete. Reasons are mandatory except for "submit".
--}}
@php
    use App\Enums\Common\Locale;
    use App\Enums\Content\WorkflowAction;

    $prefix = $type->routePrefix();
    $step = fn (WorkflowAction $action) => route('admin.content.transition', ['contentType' => $type->value, 'content' => $content->getKey(), 'action' => $action->value]);
@endphp
@can('view', $content)
    @foreach (Locale::cases() as $locale)
        <x-admin.button variant="ghost" icon="eye" :href="route('admin.content.preview', ['contentType' => $type->value, 'content' => $content->getKey(), 'locale' => $locale->value])" target="_blank" rel="noopener">{{ __('content.actions.preview', ['locale' => $locale->label()]) }}</x-admin.button>
    @endforeach
@endcan
@can('update', $content)
    <x-admin.button variant="secondary" icon="pencil" :href="route($prefix.'.edit', $content)">{{ __('admin.common.edit') }}</x-admin.button>
@endcan
@foreach (WorkflowAction::cases() as $action)
    @can('transition', [$content, $action])
        @if ($action === WorkflowAction::Schedule)
            <x-admin.button variant="secondary" icon="clock" data-modal-open="content-schedule">{{ $action->label() }}</x-admin.button>
        @else
            <x-admin.confirm-action
                :url="$step($action)"
                :title="__('content.confirm.'.$action->value.'_title')"
                :message="__('content.confirm.'.$action->value.'_message')"
                :confirm-label="$action->label()"
                :reason-required="$action->reasonRequired()"
                :variant="$action->variant()"
                :icon="$action->icon()"
            >{{ $action->label() }}</x-admin.confirm-action>
        @endif
    @endcan
@endforeach
@can('delete', $content)
    <x-admin.confirm-action :url="route($prefix.'.destroy', [$content, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
@endcan
@can('transition', [$content, WorkflowAction::Schedule])
    <x-admin.action-modal id="content-schedule" :title="__('content.confirm.schedule_title')" :message="__('content.confirm.schedule_message')" :action="$step(WorkflowAction::Schedule)" :submit="WorkflowAction::Schedule->label()" variant="primary" icon="clock">
        <x-admin.form.datetime name="scheduled_at" :label="__('content.fields.scheduled_at')" :hint="__('content.hints.scheduled_at', ['timezone' => config('admin.display_timezone')])" required />
    </x-admin.action-modal>
@endcan
