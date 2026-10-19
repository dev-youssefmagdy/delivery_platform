{{-- Global confirm dialog used by <x-admin.confirm-action> and <x-admin.form confirm="…"> (resources/js/admin/core/confirm-action.js). --}}
<div
    x-data="confirmDialog()"
    data-default-title="{{ __('admin.confirm.title') }}"
    data-default-message="{{ __('admin.confirm.message') }}"
    data-default-confirm="{{ __('admin.confirm.confirm') }}"
    x-on:keydown.escape.window="open && hide()"
>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[55] overflow-y-auto" role="alertdialog" aria-modal="true" aria-labelledby="admin-confirm-title" aria-describedby="admin-confirm-message">
            <div class="overlay-backdrop" x-on:click="hide()"></div>
            <div class="relative flex min-h-full items-center justify-center p-4">
                <div class="modal-panel max-w-md" x-trap.inert.noscroll="open">
                    <x-admin.form action="#" method="POST" :live-validation="false" x-ref="form" data-close-on-success="false">
                        <div class="flex gap-4 p-6">
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full" x-bind:class="variant === 'danger' ? 'bg-danger-50 text-danger-600' : 'bg-brand-50 text-brand-600'">
                                <x-admin.icon name="exclamation" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1 space-y-4">
                                <div>
                                    <h2 id="admin-confirm-title" class="text-base font-semibold text-slate-900" x-text="title"></h2>
                                    <p id="admin-confirm-message" class="mt-1 text-sm text-slate-600" x-text="message"></p>
                                </div>
                                <div x-show="reasonRequired">
                                    <x-admin.form.textarea name="reason" :label="__('admin.confirm.reason_label')" :hint="__('admin.confirm.reason_hint')" rows="3" :minlength="config('admin.reason.min')" :maxlength="config('admin.reason.max')" disabled />
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-slate-50">
                            <x-admin.button variant="secondary" x-ref="cancel" x-on:click="hide()">{{ __('admin.common.cancel') }}</x-admin.button>
                            <button type="submit" class="btn" x-show="mode === 'request'" x-bind:class="variant === 'danger' ? 'btn-danger' : 'btn-primary'">
                                <x-admin.spinner class="btn-spinner" />
                                <span class="btn-label" x-text="confirmLabel"></span>
                            </button>
                            <button type="button" class="btn" x-show="mode === 'ask'" x-bind:class="variant === 'danger' ? 'btn-danger' : 'btn-primary'" x-on:click="confirm()">
                                <span x-text="confirmLabel"></span>
                            </button>
                        </div>
                    </x-admin.form>
                </div>
            </div>
        </div>
    </template>
</div>
