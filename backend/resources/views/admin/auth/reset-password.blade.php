<x-admin.layouts.auth :title="__('admin.auth.reset.title')" :subtitle="__('admin.auth.reset.subtitle', ['length' => config('admin.auth.password.min_length')])">
    <x-admin.form :action="route('admin.password.update')" :live-validation="false">
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="space-y-4">
            <x-admin.form.input name="email" type="email" :label="__('admin.auth.fields.email')" :value="$email" autocomplete="username" maxlength="191" required dir="ltr" />
            <x-admin.form.input name="password" type="password" :label="__('admin.auth.fields.new_password')" autocomplete="new-password" maxlength="255" required autofocus dir="ltr" />
            <x-admin.form.input name="password_confirmation" type="password" :label="__('admin.auth.fields.password_confirmation')" autocomplete="new-password" maxlength="255" required dir="ltr" />
        </div>

        <x-admin.button type="submit" icon="check" class="mt-6 w-full">{{ __('admin.auth.reset.submit') }}</x-admin.button>
    </x-admin.form>
</x-admin.layouts.auth>
