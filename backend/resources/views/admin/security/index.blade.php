<x-admin.layouts.app :title="__('admin.security.title')">
    <x-admin.page-header
        :title="__('admin.security.title')"
        :subtitle="__('admin.security.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.security.title')]]"
    />

    @if ($mfaRequired && ! $admin->hasMfaEnabled())
        <x-admin.alert type="warning" :title="__('admin.security.mfa_required_title')" class="mb-6">
            {{ __('admin.security.mfa_required_body') }}
            <x-admin.button class="mt-3" size="sm" :href="route('admin.security.mfa')" icon="lock-closed">{{ __('admin.security.mfa_setup') }}</x-admin.button>
        </x-admin.alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <x-admin.card :title="__('admin.security.profile')">
            <x-admin.form :action="route('admin.security.profile.update')" method="PUT">
                <div class="space-y-4">
                    <x-admin.form.input name="name" :label="__('admin.admins.fields.name')" :value="$admin->name" maxlength="191" required />
                    <x-admin.form.input name="email" type="email" :label="__('admin.admins.fields.email')" :value="$admin->email" disabled dir="ltr" :hint="__('admin.security.email_hint')" />
                    <x-admin.form.input name="phone" :label="__('admin.admins.fields.phone')" :value="$admin->phone" maxlength="32" dir="ltr" />
                    <x-admin.form.enum-select name="locale" :label="__('admin.admins.fields.locale')" :enum="\App\Enums\Common\Locale::class" :value="$admin->locale" :placeholder="__('admin.admins.locale_auto')" />
                    <x-admin.detail-list>
                        <x-admin.detail-list.item :label="__('admin.admins.fields.roles')">
                            @forelse ($admin->roles as $role)
                                <x-admin.badge :label="$role->displayName()" color="info" :dot="false" />
                            @empty
                                <span class="text-slate-500">{{ __('admin.common.not_set') }}</span>
                            @endforelse
                        </x-admin.detail-list.item>
                    </x-admin.detail-list>
                </div>
                <x-admin.form.actions :submit="__('admin.common.save_changes')" />
            </x-admin.form>
        </x-admin.card>

        <x-admin.card :title="__('admin.security.password')" :subtitle="__('admin.security.password_hint', ['length' => config('admin.auth.password.min_length')])">
            <x-admin.form :action="route('admin.security.password.update')" method="PUT" :live-validation="false">
                <div class="space-y-4">
                    <x-admin.form.input name="current_password" type="password" :label="__('admin.auth.fields.current_password')" autocomplete="current-password" maxlength="255" required dir="ltr" />
                    <x-admin.form.input name="password" type="password" :label="__('admin.auth.fields.new_password')" autocomplete="new-password" maxlength="255" required dir="ltr" />
                    <x-admin.form.input name="password_confirmation" type="password" :label="__('admin.auth.fields.password_confirmation')" autocomplete="new-password" maxlength="255" required dir="ltr" />
                </div>
                <x-admin.form.actions :submit="__('admin.security.change_password')" />
            </x-admin.form>
        </x-admin.card>

        <x-admin.card :title="__('admin.security.mfa')">
            @if ($admin->hasMfaEnabled())
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <x-admin.badge :label="__('admin.security.mfa_on')" color="success" />
                    <span class="text-sm text-slate-500">{{ __('admin.security.recovery_remaining', ['count' => $recoveryCodesRemaining]) }}</span>
                </div>

                <div class="space-y-6">
                    <x-admin.form :action="route('admin.security.mfa.regenerate')" :live-validation="false">
                        <x-admin.form.input name="password" type="password" :label="__('admin.security.regenerate_password')" autocomplete="current-password" maxlength="255" required dir="ltr" />
                        <x-admin.form.actions :submit="__('admin.security.regenerate')" submit-icon="refresh" />
                    </x-admin.form>

                    @unless ($mfaRequired)
                        <x-admin.form :action="route('admin.security.mfa.disable')" :live-validation="false" :confirm="__('admin.security.disable_confirm')">
                            <x-admin.form.input name="password" type="password" :label="__('admin.security.disable_password')" autocomplete="current-password" maxlength="255" required dir="ltr" />
                            <x-admin.form.actions :submit="__('admin.security.disable')" submit-icon="x-mark" />
                        </x-admin.form>
                    @else
                        <x-admin.alert type="info">{{ __('admin.security.mfa_cannot_disable') }}</x-admin.alert>
                    @endunless
                </div>
            @else
                <x-admin.empty-state icon="lock-closed" :title="__('admin.security.mfa_off')" :description="__('admin.security.mfa_off_hint')" compact>
                    <x-admin.button :href="route('admin.security.mfa')" icon="lock-closed">{{ __('admin.security.mfa_setup') }}</x-admin.button>
                </x-admin.empty-state>
            @endif
        </x-admin.card>

        <x-admin.card :title="__('admin.security.sessions')" :subtitle="__('admin.security.sessions_hint')">
            <ul class="divide-y divide-slate-100">
                @foreach ($sessions as $session)
                    @php($current = $session->session_id === $currentSessionId)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800">
                                {{ \Illuminate\Support\Str::limit($session->user_agent ?? __('admin.security.unknown_device'), 60) }}
                                @if ($current)
                                    <x-admin.badge :label="__('admin.security.this_device')" color="success" :dot="false" />
                                @endif
                            </p>
                            <p class="ltr-only text-xs text-slate-500">{{ $session->ip ?? '—' }} · {{ $session->last_active_at?->copy()->setTimezone(config('admin.display_timezone'))->format('Y-m-d H:i') }}</p>
                        </div>
                        @unless ($current)
                            <x-admin.confirm-action
                                :url="route('admin.security.sessions.destroy', $session->id)"
                                method="DELETE"
                                :title="__('admin.security.revoke_title')"
                                :message="__('admin.security.revoke_message')"
                                :confirm-label="__('admin.security.revoke')"
                                class="btn-sm"
                                icon="x-mark"
                            >{{ __('admin.security.revoke') }}</x-admin.confirm-action>
                        @endunless
                    </li>
                @endforeach
            </ul>

            @if ($sessions->count() > 1)
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <x-admin.confirm-action
                        :url="route('admin.security.sessions.destroy-others')"
                        method="DELETE"
                        :title="__('admin.security.revoke_others_title')"
                        :message="__('admin.security.revoke_others_message')"
                        :confirm-label="__('admin.security.revoke_others')"
                        icon="x-mark"
                    >{{ __('admin.security.revoke_others') }}</x-admin.confirm-action>
                </div>
            @endif
        </x-admin.card>
    </div>
</x-admin.layouts.app>
