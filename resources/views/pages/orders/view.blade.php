@php

use App\Http\Controllers\OrderController;
use App\Products;
use App\Transactions;
use App\User;

$transaction = Transactions::find(request()->route('id'));
if ($transaction == null) return redirect('/orders')->with('error', 'Invalid order.')->send();

$transaction_items = explode(", ", $transaction->products);
$transaction_returned = OrderController::checkReturned($transaction->id);
@endphp
@extends('layouts.default', ['page' => 'orders'])
@section('content')
<h2 class="title has-text-weight-bold">View Order</h2>
<div class="columns">
    <div class="column">
        @include('includes.messages')
        <br>
        <h4>Order ID: {{ request()->route('id') }}</h4>
        <h4>Date: {{ $transaction->created_at->format('M jS Y h:ia') }}</h4>
        <h4>Purchaser: <a href="/users/info/{{ $transaction->purchaser_id }}">{{ User::find($transaction->purchaser_id)->full_name }}</a></h4>
        <h4>Cashier: <a href="/users/info/{{ $transaction->cashier_id }}">{{ User::find($transaction->cashier_id)->full_name }}</a></h4>
        <h4>Total Price: ${{ number_format($transaction->total_price, 2) }}</h4>
        <h4>Status: {{ $transaction_returned ? "" : "Not" }} Returned</h4>
        <br>
        @if(!$transaction_returned)
            <form>
                <input type="hidden" id="transaction_id" value="{{ $transaction->id }}">
                <a href="javascript:;" data-toggle="modal" data-target="#returnModal" class="button is-danger">Return</a>
            </form>
        @endif
    </div>
    <div class="column">
        <h2 align="center">Items</h2>
        <table id="product_list">
            <thead>
                <th>Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Item Price</th>
                <th></th>
            </thead>
            <tbody>
                @foreach($transaction_items as $product)
                @php
                $item_info = OrderController::deserializeProduct($product);
                @endphp
                <tr>
                    <td class="table-text">
                        <div>{{ $item_info['name'] }}</div>
                    </td>
                    <td class="table-text">
                        <div>${{ number_format($item_info['price'], 2) }}</div>
                    </td>
                    <td class="table-text">
                        <div>{{ $item_info['quantity'] }}</div>
                    </td>
                    <td class="table-text">
                        <div>${{ number_format($item_info['price'] * $item_info['quantity'], 2) }}</div>
                    </td>
                    <td class="table-text">
                        <div>
                            @if($transaction->status == 0 && $item_info['returned'] < $item_info['quantity']) 
                                <form>
                                    <input type="hidden" id="item_id" value="{{ $item_info['id'] }}">
                                    <a href="javascript:;" data-toggle="modal"
                                        onclick="window.location='/orders/return/item/{{ $item_info['id'] }}/{{ $transaction->id }}';"
                                        class="button is-danger">Return ({{ $item_info['quantity'] - $item_info['returned'] }})</a>
                                </form>
                            @else
                                <div>Returned</div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div id="returnModal" class="modal fade" role="dialog">
    <div class="modal-dialog ">
        <form action="" id="returnForm" method="get">
            <div class="modal-content">
                <div class="modal-body">
                    @csrf
                    <p class="text-center">Are you sure you want to return this transaction?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button is-info" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="" class="button is-danger" data-dismiss="modal" onclick="returnData()">Return</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('#product_list').DataTable({
            "paging": false,
            "scrollY": "27vw",
            "scrollCollapse": true,
            "columnDefs": [
                { 
                    "orderable": false, 
                    "targets": [4]
                }
            ]
        });
    });

    function returnData() {
        let url = '{{ route("return_order", ":id") }}';
        url = url.replace(':id', document.getElementById('transaction_id').value);
        $("#returnForm").attr('action', url);
        $("#returnForm").submit();
    }
</script>
@endsection