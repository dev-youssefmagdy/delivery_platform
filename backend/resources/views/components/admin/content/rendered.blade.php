@props(['html'])
{{-- Sanitized HTML produced by the CMS (ContentRenderer / HtmlSanitizer at save time). Never pass user input here. --}}
<div {{ $attributes->class(['cms-prose']) }}>{!! $html !!}</div>
