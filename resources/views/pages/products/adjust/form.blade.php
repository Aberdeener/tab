<div align="center">
    <h3>Adjust</h3>
    <p>Product: {{ $product->name }} <a href="/products/edit/{{ $product->id }}">(Edit)</a></p>
    <hr>
    @if($product->unlimited_stock)
    <i>No available options</i>
    @else
    <form method="POST" action="/products/adjust/commit">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <span>Add/Subtract Stock</span><br>
        <input type="number" step="1" name="adjust_stock" value="0"><br><br>
        @if($product->box_size != -1)
        <span>Add/Subtract Box</span><br>
        <input type="number" step="1" name="adjust_box" value="0"><br><br>
        @endif
        <button class="btn btn-success">Update</button>
    </form>
    @endif
</div>