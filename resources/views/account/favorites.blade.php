<x-layouts.account title="Saved products">
    <x-page-header title="Saved products" eyebrow="Favourites"/>
    @if ($products->isEmpty())
        <x-empty title="No saved products" icon="heart">Use “Save product” on any product page to build a shortlist.</x-empty>
    @else
        <div class="grid grid-cols-2 gap-x-5 gap-y-10 xl:grid-cols-3">
            @foreach ($products as $product)<x-product-card :product="$product"/>@endforeach
        </div>
    @endif
</x-layouts.account>
