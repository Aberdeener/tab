@extends('layouts.default', ['page' => 'orders'])
@section('content')
<h2 class="title has-text-weight-bold">Order List</h2>
<div id="loading" align="center">
    <img src="{{ url('img/loader.gif') }}" alt="Loading..." class="loading-spinner" />
</div>
<div class="columns box">
    <div class="column" id="order_container" style="visibility: hidden;">
        @include('includes.messages')
        <table id="order_list">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Purchaser</th>
                    <th>Cashier</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    @permission(\App\Helpers\Permission::ORDERS_VIEW)
                    <th></th>
                    @endpermission
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr>
                    <td>
                        <div>{{ $transaction->created_at->format('M jS Y h:ia') }}</div>
                    </td>
                    <td>
                        <div>
                            @permission(\App\Helpers\Permission::USERS_VIEW)
                                <a href="{{ route('users_view', $transaction->purchaser) }}">{{ $transaction->purchaser->full_name }}</a>
                            @else
                                {{ $transaction->purchaser->full_name }}
                            @endpermission
                        </div>
                    </td>
                    <td>
                        <div>{{ $transaction->cashier->full_name }}</div>
                    </td>
                    <td>
                        <div>{{ $transaction->total_price }}</div>
                    </td>
                    <td>
                        <div>
                            @switch($transaction->getReturnStatus())
                                @case('NOT_RETURNED')
                                    <span class="tag is-success is-medium">Normal</span>
                                @break
                                @case('FULLY_RETURNED')
                                    <span class="tag is-danger is-medium">Returned</span>
                                @break
                                @case('PARTIAL_RETURNED')
                                    <span class="tag is-warning is-medium">Semi Returned</span>
                                @break
                            @endswitch
                        </div>
                    </td>
                    @permission(\App\Helpers\Permission::ORDERS_VIEW)
                    <td>
                        <div><a href="{{ route('orders_view', $transaction->id) }}">View</a></div>
                    </td>
                    @endpermission
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#order_list').DataTable({
            "paging": false,
            "scrollY": "49vh",
            "scrollCollapse": true,
            "order": [],
            "columnDefs": [{
                "orderable": false,
                "searchable": false,
                "targets": [
                    4,
                    @permission(\App\Helpers\Permission::ORDERS_VIEW)
                    5
                    @endpermission
                ]
            }]
        });
        $('#loading').hide();
        $('#order_container').css('visibility', 'visible');
    });
</script>
@endsection
