@extends('layouts.default', ['page' => 'orders'])
@section('content')
<h2 class="title has-text-weight-bold">View Order</h2>
<div class="columns box">
    <div class="column">
        @include('includes.messages')
        <p><strong>Order ID:</strong> {{ $transaction->id }}</p>
        <p><strong>Date:</strong> {{ $transaction->created_at->format('M jS Y h:ia') }}</p>
        <p><strong>Rotation:</strong> {{ $transaction->rotation->name }}</p>
        <p><strong>Purchaser:</strong> @permission(\App\Helpers\Permission::USERS_VIEW) <a href="{{ route('users_view', $transaction->purchaser_id) }}">{{ $transaction->purchaser->full_name }}</a> @else {{ $transaction->purchaser->full_name }} @endpermission</p>
        <p><strong>Cashier:</strong> @permission(\App\Helpers\Permission::USERS_VIEW) <a href="{{ route('users_view', $transaction->cashier_id) }}">{{ $transaction->cashier->full_name }}</a> @else {{ $transaction->cashier->full_name }} @endpermission</p>
        <p><strong>Total Price:</strong> ${{ number_format($transaction->total_price, 2) }}</p>
        <p><strong>Status:</strong> @switch($transaction_returned) @case(0) Not Returned @break @case(1) Returned @break @case(2) Semi Returned @break @endswitch</p>
        <br>
        @if($transaction_returned !== 1 && hasPermission(\App\Helpers\Permission::ORDERS_RETURN))
        <button class="button is-danger is-outlined" type="button" onclick="openModal();">
            <span>Return</span>
            <span class="icon is-small">
                <i class="fas fa-undo"></i>
            </span>
        </button>
        @endif
    </div>
    <div class="column">
        <h4 class="title has-text-weight-bold is-4">Items</h4>
        <div id="loading" align="center">
            <img src="{{ url('img/loader.gif') }}" alt="Loading..." class="loading-spinner" />
        </div>
        <div id="table_container" style="visibility: hidden;">
            <table id="product_list">
                <thead>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Item Price</th>
                    @permission(\App\Helpers\Permission::ORDERS_RETURN)
                    <th></th>
                    @endpermission
                </thead>
                <tbody>
                    @foreach($transaction_items as $product)
                    <tr>
                        <td>
                            <div>{{ $product['product']['name'] }}</div>
                        </td>
                        <td>
                            <div>${{ number_format($product['price'], 2) }}</div>
                        </td>
                        <td>
                            <div>{{ $product['quantity'] }}</div>
                        </td>
                        <td>
                            <div>${{ number_format($product['price'] * $product['quantity'], 2) }}</div>
                        </td>
                        @permission(\App\Helpers\Permission::ORDERS_RETURN)
                        <td>
                            <div>
                                @if(!$transaction->returned && $product['returned'] < $product['quantity'])
                                    <button class="button is-danger is-small"  onclick="openProductModal({{ $product['product_id'] }});">Return ({{ $product['quantity'] - $product['returned'] }})</button>
                                @else
                                    <div><i>Returned</i></div>
                                @endif
                            </div>
                        </td>
                        @endpermission
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@permission(\App\Helpers\Permission::ORDERS_RETURN)
<div class="modal modal-order">
    <div class="modal-background" onclick="closeModal();"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Confirmation</p>
        </header>
        <section class="modal-card-body">
            <p>Are you sure you want to return this transaction?</p>
            <form action="" id="returnForm" method="GET">
                @csrf
            </form>
        </section>
        <footer class="modal-card-foot">
            <button class="button is-success" type="submit" onclick="returnData();">Confirm</button>
            <button class="button" onclick="closeModal();">Cancel</button>
        </footer>
    </div>
</div>

<div class="modal modal-product">
    <div class="modal-background" onclick="closeProductModal();"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">Confirmation</p>
        </header>
        <section class="modal-card-body">
            <p>Are you sure you want to return this product?</p>
            <form action="" id="returnItemForm" method="GET">
                @csrf
            </form>
        </section>
        <footer class="modal-card-foot">
            <button class="button is-success" type="submit" onclick="returnProductData();">Confirm</button>
            <button class="button" onclick="closeProductModal();">Cancel</button>
        </footer>
    </div>
</div>
@endpermission

<script type="text/javascript">
    $(document).ready(function() {
        $('#product_list').DataTable({
            "paging": false,
            "scrollY": "49vh",
            "scrollCollapse": true,
            @permission(\App\Helpers\Permission::ORDERS_RETURN)
            "columnDefs": [{
                "orderable": false,
                "searchable": false,
                "targets": [4]
            }]
            @endpermission
        });
        $('#loading').hide();
        $('#table_container').css('visibility', 'visible');
    });

    @permission(\App\Helpers\Permission::ORDERS_RETURN)
        const modal_order = document.querySelector('.modal-order');

        function openModal() {
            modal_order.classList.add('is-active');
        }

        function closeModal() {
            modal_order.classList.remove('is-active');
        }

        function returnData() {
            let url = '{{ route("orders_return", ":id") }}';
            url = url.replace(':id', {{ $transaction->id }});
            $("#returnForm").attr('action', url);
            $("#returnForm").submit();
        }

        let product = null;
        const modal_product = document.querySelector('.modal-product');

        function openProductModal(return_product) {
            product = return_product;
            modal_product.classList.add('is-active');
        }

        function closeProductModal() {
            product = null;
            modal_product.classList.remove('is-active');
        }

        function returnProductData() {
            let url = '{{ route("orders_return_item", [":order", ":product"]) }}';
            url = url.replace(':order', {{ $transaction->id }});
            url = url.replace(':product', product);
            $("#returnItemForm").attr('action', url);
            $("#returnItemForm").submit();
        }
    @endpermission
</script>
@endsection
