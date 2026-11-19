{{ $body }}

@if ($note !== null)
{{ __('offer::booking.notify.message', ['message' => $note]) }}

@endif
{{ __('offer::booking.notify.open_app', ['section' => $section]) }}
