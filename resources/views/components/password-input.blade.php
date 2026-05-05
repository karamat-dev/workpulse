@props(['disabled' => false])

<div class="relative">
    <input
        @disabled($disabled)
        {{ $attributes->merge([
            'type' => 'password',
            'class' => 'block w-full pr-14 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm',
        ]) }}
    >
    <button
        type="button"
        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-gray-500 hover:text-gray-800 focus:outline-none focus:text-gray-800"
        aria-label="{{ __('Show password') }}"
        onclick="
            const input = this.previousElementSibling;
            input.type = input.type === 'text' ? 'password' : 'text';
            const revealed = input.type === 'text';
            this.querySelector('[data-password-eye]').hidden = revealed;
            this.querySelector('[data-password-eye-off]').hidden = !revealed;
            this.setAttribute('aria-label', revealed ? '{{ __('Hide password') }}' : '{{ __('Show password') }}');
        "
    >
        <svg data-password-eye width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M2.1 12s3.6-7 9.9-7 9.9 7 9.9 7-3.6 7-9.9 7-9.9-7-9.9-7Z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
        <svg data-password-eye-off hidden width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9.9 4.2A10.7 10.7 0 0 1 12 4.0c6.3 0 9.9 8 9.9 8a18.4 18.4 0 0 1-2.8 3.9"></path>
            <path d="M14.1 14.1A3 3 0 0 1 9.9 9.9"></path>
            <path d="M6.6 6.6A18.5 18.5 0 0 0 2.1 12s3.6 7 9.9 7a10.8 10.8 0 0 0 5.4-1.5"></path>
            <path d="M2 2l20 20"></path>
        </svg>
    </button>
</div>
