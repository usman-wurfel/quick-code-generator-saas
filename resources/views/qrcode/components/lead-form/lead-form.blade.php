@if ($composer->shouldRender())

{!! $composer->styles() !!}

@php
$mode = isset($mode) ? $mode : 'inline';
@endphp

<script>
    window.__LEAD_FORM_SUCCESS_MESSAGE__ = "{{ t('Thank you for submitting the form') }}";
</script>

<div class="lead-form lead-form-{{ $composer->id() }} {{ $mode }}" data-id="{{ $composer->id() }}">
    @include('qrcode.components.lead-form.close-button')

    <div class="questions">

        @foreach ($composer->questions() as $question)

        @include ('qrcode.components.lead-form.question', compact('question'))

        @endforeach

    </div>

    @include('qrcode.components.lead-form.footer')

    @include('qrcode.components.lead-form.success-page')

</div>

@endif