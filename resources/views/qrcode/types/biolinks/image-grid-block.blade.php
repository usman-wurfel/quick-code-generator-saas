<div class="block {{ $block::slug() }}-block" id="{{ $model->getId() }}">
    @foreach ($model->field('items') as $item)
    <a href="{{ @$item['url'] ?? 'javascript: return 0;'}}" style="
        background-image: url({{ $model->fileUrlById(@$item['image'])}});
    "></a>
    @endforeach
</div>