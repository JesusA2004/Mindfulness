<x-mail::layout>
    @php
        $resolvedLoginUrl = $loginUrl
            ?? (config('app.frontend_url')
                ? rtrim(config('app.frontend_url'), '/')
                : rtrim(config('app.url'), '/') . '/login');
    @endphp

    <x-slot:header>
        <x-mail::header :login-url="$resolvedLoginUrl" :logo-cid="$logoCid ?? null">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>

    {{ $slot }}

    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>{{ $subcopy }}</x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ config('app.name') }}. @lang('Todos los derechos reservados.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
