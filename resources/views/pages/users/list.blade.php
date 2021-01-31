@php

use App\User;
use App\Roles;
$users_view = Auth::user()->hasPermission('users_view');
$users_manage = Auth::user()->hasPermission('users_manage');
@endphp
@extends('layouts.default')
@section('content')
<h2 class="title has-text-weight-bold">User List</h2>
<div id="loading" align="center">
    <img src="{{ url('loader.gif') }}" alt="Loading..." class="loading-spinner" />
</div>
<div class="columns box">
    <div class="column" id="user_container" style="visibility: hidden;">
        @include('includes.messages')
        <table id="user_list">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Balance</th>
                    <th>Role</th>
                    @if ($users_view)
                    <th></th>
                    @endif
                    @if ($users_manage)
                    <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach (User::where('deleted', false)->get() as $user)
                <tr>
                    <td>
                        <div>{{ $user->full_name }}</div>
                    </td>
                    <td>
                        <div>{{ $user->username }}</div>
                    </td>
                    <td>
                        <div>${{ number_format($user->balance, 2) }}</div>
                    </td>
                    <td>
                        <div>{{ $user->role->name }}</div>
                    </td>
                    @if ($users_view)
                    <td>
                        <div><a href="{{ route('users_view', $user->id) }}">View</a></div>
                    </td>
                    @endif
                    @if ($users_manage)
                    @if (Auth::user()->role->canInteract($user->role))
                    <td>
                        <div><a href="{{ route('users_edit', $user->id) }}">Edit</a></div>
                    </td>
                    @else
                    <td>
                        <div class="control">
                            <button class="button is-warning" disabled>
                                <span class="icon">
                                    <i class="fas fa-lock"></i>
                                </span>
                            </button>
                        </div>
                    </td>
                    @endif
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#user_list').DataTable({
            "paging": false,
            "scrollY": "49vh",
            "scrollCollapse": true,
            "columnDefs": [{
                "orderable": false,
                "searchable": false,
                "targets": [
                    @if($users_view && $users_manage)
                    4,
                    5
                    @elseif($users_view && !$users_manage)
                    4
                    @elseif(!$users_view && $users_manage)
                    4
                    @endif
                ]
            }]
        });
        $('#loading').hide();
        $('#user_container').css('visibility', 'visible');
    });
</script>
@endsection