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
            <div class="menu-item-title">
                {{ $item['name'] }}
            </div>

            <div class="menu-item-ingredients">
                {{ @$item['ingredients']}}
            </div>

            @if(!empty($composer->foodAllergens($item)))
            <div class="food-allergens">
                @foreach ($composer->foodAllergens($item) as $fItem)
                <div class="food-allergens-item">
                    @if ($composer->findFileUrl(@$fItem['icon']) && $composer->foodAllergensDisplayIcon())
                    <div class="food-allergens-item-icon"
                        style="background-image: url({{ $composer->findFileUrl($fItem['icon']) }});"></div>
                    @endif

                    @if ($composer->foodAllergensDisplayText())
                    <div class="food-allergens-item-name">
                        {{ @$fItem['name'] }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="menu-item-price">
            {{ @$item['price'] }}
        </div>
    </div>
</div>