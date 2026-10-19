<x-admin.layouts.auth :title="__('admin.auth.forgot.title')" :subtitle="__('admin.auth.forgot.subtitle')">
    <x-admin.form :action="route('admin.password.email')" :live-validation="false">
        <x-admin.form.input name="email" type="email" :label="__('admin.auth.fields.email')" autocomplete="username" maxlength="191" required autofocus dir="ltr" />

        <x-admin.button type="submit" icon="check" class="mt-6 w-full">{{ __('admin.auth.forgot.submit') }}</x-admin.button>

        <p class="mt-4 text-center text-sm">
            <a href="{{ route('admin.login') }}" class="text-brand-700 hover:underline">{{ __('admin.auth.back_to_login') }}</a>
        </p>
    </x-admin.form>
</x-admin.layouts.auth>
