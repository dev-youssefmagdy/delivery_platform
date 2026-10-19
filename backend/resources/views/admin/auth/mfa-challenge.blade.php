<x-admin.layouts.auth :title="__('admin.auth.mfa.challenge_title')" :subtitle="__('admin.auth.mfa.challenge_subtitle')">
    <x-admin.form :action="route('admin.mfa.verify')" :live-validation="false">
        <x-admin.form.input
            name="code"
            :label="__('admin.auth.fields.code')"
            :hint="__('admin.auth.mfa.challenge_hint')"
            inputmode="text"
            autocomplete="one-time-code"
            maxlength="11"
            required
            autofocus
            dir="ltr"
        />

        <x-admin.button type="submit" icon="check" class="mt-6 w-full">{{ __('admin.auth.mfa.verify') }}</x-admin.button>

        <p class="mt-4 text-center text-sm">
            <a href="{{ route('admin.login') }}" class="text-brand-700 hover:underline">{{ __('admin.auth.back_to_login') }}</a>
        </p>
    </x-admin.form>
</x-admin.layouts.auth>
