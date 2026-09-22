{{--
    Brand glyph for a category. Usage:
        @include('partials.brand-icon', ['icon' => $category->icon, 'class' => 'h-5 w-5'])

    These are simplified marks in a single currentColor path, not the exact
    trademarked logos — enough to be recognisable, safe on IP, and they inherit
    text colour so they work in light and dark. Unknown keys fall back to a tag.
--}}
@php
    $class = $class ?? 'h-5 w-5';
    $icon  = $icon ?? '';
@endphp

@switch($icon)
    @case('facebook')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12Z"/></svg>
        @break
    @case('instagram')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"/></svg>
        @break
    @case('tiktok')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.5 3c.3 2 1.5 3.6 3.5 4v2.7c-1.3 0-2.5-.4-3.5-1v6.1c0 3.2-2.4 5.5-5.4 5.5A5.3 5.3 0 0 1 5.6 15c0-3.1 2.9-5.5 6-4.9v2.8a2.5 2.5 0 0 0-3.2 2.4c0 1.4 1.1 2.4 2.5 2.4 1.5 0 2.6-1.1 2.6-2.9V3h3Z"/></svg>
        @break
    @case('twitter')
    @case('x')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 3h3l-6.6 7.6L22 21h-5.9l-4.3-5.6L6.7 21H3.7l7-8.1L2.5 3h6l3.9 5.2L17.5 3Zm-2 16h1.6L8.6 4.6H6.9L15.5 19Z"/></svg>
        @break
    @case('vpn')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
        @break
    @case('google')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.8 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.5a4.7 4.7 0 0 1-2 3.1v2.6h3.2c1.9-1.7 3-4.3 3-7.5Z"/><path d="M12 22c2.7 0 5-.9 6.7-2.4l-3.2-2.6c-.9.6-2.1 1-3.5 1a6 6 0 0 1-5.6-4.1H3.1v2.6A10 10 0 0 0 12 22Z" opacity=".7"/><path d="M6.4 13.9a6 6 0 0 1 0-3.8V7.5H3.1a10 10 0 0 0 0 9l3.3-2.6Z" opacity=".5"/><path d="M12 6.1c1.5 0 2.8.5 3.9 1.5l2.9-2.9A10 10 0 0 0 3.1 7.5l3.3 2.6A6 6 0 0 1 12 6.1Z" opacity=".85"/></svg>
        @break
    @case('texting')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.5 7.2L3 21l1.8-6.5A8 8 0 1 1 21 12Z"/><path d="M8.5 11h7M8.5 14h4"/></svg>
        @break
    @case('discord')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.5 5.6A16 16 0 0 0 15.5 4l-.3.5c1.6.4 2.9 1 4 1.7-2.9-1.4-6.5-1.5-9.5 0 1.1-.7 2.4-1.3 4-1.7L13.4 4A16 16 0 0 0 4.5 5.6C2.3 9 1.7 12.3 2 15.6a16 16 0 0 0 4.9 2.5l.7-1.1c-.7-.3-1.4-.6-2-1l.5-.4c3.6 1.7 7.6 1.7 11.2 0l.5.4c-.6.4-1.3.7-2 1l.7 1.1a16 16 0 0 0 4.9-2.5c.4-3.9-.6-7.2-2.6-10Zm-10 7.9c-.8 0-1.4-.7-1.4-1.6 0-.9.6-1.6 1.4-1.6.8 0 1.5.8 1.4 1.6 0 .9-.6 1.6-1.4 1.6Zm5 0c-.8 0-1.4-.7-1.4-1.6 0-.9.6-1.6 1.4-1.6.8 0 1.5.8 1.4 1.6 0 .9-.6 1.6-1.4 1.6Z"/></svg>
        @break
    @case('linkedin')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4.98 3.5A2.5 2.5 0 1 0 5 8.5a2.5 2.5 0 0 0 0-5ZM3 9h4v12H3V9Zm6 0h3.8v1.7h.05c.53-1 1.8-2 3.7-2 4 0 4.7 2.6 4.7 6V21h-4v-5.3c0-1.3 0-2.9-1.8-2.9s-2 1.4-2 2.8V21H9V9Z"/></svg>
        @break
    @case('mail')
    @case('mails')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 6 8-6"/></svg>
        @break
    @case('netflix')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 2h3.2l3.8 10.7V2H17v20h-3.2L9.9 10.9V22H7V2Z"/></svg>
        @break
    @case('quora')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3a8 8 0 0 0-6 13.3c1.5 1.7 3.7 2.7 6 2.7.9 0 1.8-.1 2.6-.4.6.7 1.5 1.4 2.7 1.4 1.6 0 2.6-1 2.9-2.4h-1.8c-.2.5-.5.7-1 .7-.5 0-.9-.3-1.3-.8A8 8 0 0 0 12 3Zm0 2.6c2.7 0 4.2 2.3 4.2 5.4 0 .8-.1 1.5-.3 2.1-.6-.7-1.4-1.2-2.5-1.2v1.6c.6 0 1 .3 1.4.8-.8.5-1.7.7-2.8.7-2.7 0-4.2-2.3-4.2-5.4S9.3 5.6 12 5.6Z"/></svg>
        @break
    @case('reddit')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 11.8a2.1 2.1 0 0 0-3.6-1.4c-1.4-.9-3.2-1.5-5.2-1.6l1-4.2 3 .7a1.5 1.5 0 1 0 .2-1.4l-3.6-.8c-.3-.1-.5.1-.6.4l-1.1 4.7c-2 .1-3.9.7-5.3 1.6a2.1 2.1 0 1 0-2.3 3.4 4 4 0 0 0 0 .7c0 3.3 3.6 5.9 8 5.9s8-2.6 8-5.9a4 4 0 0 0 0-.7c.8-.4 1.4-1.1 1.4-2.1ZM8 13.5a1.4 1.4 0 1 1 2.8 0 1.4 1.4 0 0 1-2.8 0Zm7.9 3.6c-1 1-3.9 1-4.9 0-.2-.2-.5-.2-.6 0-.2.1-.2.4 0 .6 1.5 1.4 4.6 1.4 6.1 0 .2-.2.2-.5 0-.6-.1-.2-.4-.2-.6 0Zm-.6-2.2a1.4 1.4 0 1 1 0-2.8 1.4 1.4 0 0 1 0 2.8Z"/></svg>
        @break
    @case('snapchat')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2c2.3 0 4.2 1.9 4.3 4.2l.1 2c0 .3.3.5.6.4l.9-.3c.6-.2 1.3.5 1 1.1-.3.6-1.2.9-1.8 1.1-.3.1-.4.4-.3.7.5 1.4 1.7 2.6 3.1 3 .4.2.4.7 0 .9-.7.4-1.6.5-2 .8-.2.2 0 .6-.2.9-.2.2-.7.1-1.3.1-.8 0-1.4.1-2 .7-.5.5-1.3 1.1-2.4 1.1s-1.9-.6-2.4-1.1c-.6-.6-1.2-.7-2-.7-.6 0-1.1.1-1.3-.1-.2-.3 0-.7-.2-.9-.4-.3-1.3-.4-2-.8-.4-.2-.4-.7 0-.9 1.4-.4 2.6-1.6 3.1-3 .1-.3 0-.6-.3-.7-.6-.2-1.5-.5-1.8-1.1-.3-.6.4-1.3 1-1.1l.9.3c.3.1.6-.1.6-.4l.1-2C7.8 3.9 9.7 2 12 2Z"/></svg>
        @break
    @case('telegram')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.3 18.6 20c-.2 1.1-.9 1.4-1.8.9l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.3-5 9.2-8.3c.4-.4-.1-.6-.6-.2L6.1 13.4l-4.9-1.5c-1.1-.3-1.1-1 .2-1.5l19.1-7.4c.9-.3 1.7.2 1.4 1.3Z"/></svg>
        @break
    @case('twitch')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 3 3 6.5V19h4v2.5h2.5L12 19h4l5-5V3H4Zm15 10-3 3h-4l-2.5 2.5V16H7V5h12v8Z"/><path d="M14.5 8h-1.7v4h1.7V8Zm-4 0H8.8v4h1.7V8Z"/></svg>
        @break
    @case('youtube')
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23 12s0-3.2-.4-4.7a2.5 2.5 0 0 0-1.7-1.8C19.3 5 12 5 12 5s-7.3 0-8.9.5A2.5 2.5 0 0 0 1.4 7.3C1 8.8 1 12 1 12s0 3.2.4 4.7a2.5 2.5 0 0 0 1.7 1.8C4.7 19 12 19 12 19s7.3 0 8.9-.5a2.5 2.5 0 0 0 1.7-1.8C23 15.2 23 12 23 12Zm-13 3V9l5.2 3-5.2 3Z"/></svg>
        @break
    @default
        <svg class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 13 13 20.5a2 2 0 0 1-2.8 0l-6.7-6.7a2 2 0 0 1-.5-1.9L4.6 5A2 2 0 0 1 6 3.6l6-1.6a2 2 0 0 1 1.9.5l6.6 6.6a2 2 0 0 1 0 2.9Z"/><circle cx="8.5" cy="8.5" r="1.3"/></svg>
@endswitch