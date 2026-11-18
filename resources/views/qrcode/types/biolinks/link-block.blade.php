<div class="block link-block" id="{{ $model->getId() }}">
    <a href="{{ $model->field('url', '#') }}" {!! $block->linkTarget() !!}>
        {!! $block->html() !!}
    </a>
</div>