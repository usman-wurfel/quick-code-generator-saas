<div class="block image-block {{ $block->classString()}}" id="{{ $model->getId() }}">
    <img src="{{ $model->fileUrl('image') }}" />

    @if ($model->notEmpty('text'))
    <div class="caption">{{ $model->field('text') }}</div>
    @endif

    @if ($model->notEmpty('url'))
    <a href="{{ $model->field('url') }}"></a>
    @endif
</div>