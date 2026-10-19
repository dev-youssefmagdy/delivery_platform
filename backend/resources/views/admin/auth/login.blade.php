<x-admin.layouts.auth :title="__('admin.auth.login.title')" :subtitle="__('admin.auth.login.subtitle')">
    <x-admin.form :action="route('admin.login.store')" :live-validation="false">
        <div class="space-y-4">
            <x-admin.form.input name="email" type="email" :label="__('admin.auth.fields.email')" autocomplete="username" maxlength="191" required autofocus dir="ltr" />
            <x-admin.form.input name="password" type="password" :label="__('admin.auth.fields.password')" autocomplete="current-password" maxlength="255" required dir="ltr" />
            <x-admin.form.checkbox name="remember" :label="__('admin.auth.remember')" />
        </div>

        <x-admin.button type="submit" icon="logout" class="mt-6 w-full">{{ __('admin.auth.login.submit') }}</x-admin.button>

        <p class="mt-4 text-center text-sm">
            <a href="{{ route('admin.password.request') }}" class="text-brand-700 hover:underline">{{ __('admin.auth.forgot_link') }}</a>
        </p>
    </x-admin.form>
</x-admin.layouts.auth>
