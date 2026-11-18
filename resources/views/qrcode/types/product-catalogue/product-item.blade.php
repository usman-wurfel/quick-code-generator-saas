<div class="menu-item {{ $composer->itemLayoutClass($item) }}" category-slug="{{ $item['category'] }}">

    @if ($composer->shouldShowItemImage($item))
    <div class="image-container" style="{{ $composer->imageContainerStyleAttribute($item)}}">
        @if (!$composer->menuItemHorizontalLayout($item))
        <img src="{{ $composer->itemImage($item) }}" alt="{{ $item['name'] }}" />
        @endif
    </div>
    @endif

    <div class="menu-item-details">
        <div class="title-ingredients">
            @if (!empty(@$item['product_url']))
            <a class="menu-item-title" href="{{ $item['product_url'] }}" {!! $composer->productButtonTarget() !!}>
                {{ $item['name'] }}
            </a>
            @else
            <div class="menu-item-title">
                {{ $item['name'] }}
            </div>
            @endif

            <div class="menu-item-ingredients">
                {{ @$item['ingredients']}}
            </div>
        </div>

        <div class="menu-item-price">
            {{ @$item['price'] }}
        </div>


        @if (!empty(@$item['product_url']))
        <a class="button order-button" href="{{ $item['product_url'] }}" {!! $composer->productButtonTarget() !!}>
            {{ $composer->productButtonText() }}
        </a>
        @endif

    </div>
</div>