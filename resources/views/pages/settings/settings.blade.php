@extends('layouts.default')
@section('content')
@php
use App\Http\Controllers\SettingsController;
use App\Roles;
@endphp
<h2 class="title has-text-weight-bold">Settings</h2>
<div class="columns">
    <div class="column box is-3">
        <h4 class="title has-text-weight-bold is-4">General</h4>
        @include('includes.messages')
        <form action="/settings" id="settings" method="POST">
            @csrf

            <div class="field">
                <label class="label">GST<sup style="color: red">*</sup></label>
                <div class="control has-icons-left">
                    <span class="icon is-small is-left">
                        <i class="fas fa-percent"></i>
                    </span>
                    <input type="number" step="0.01" name="gst" class="input" value="{{ SettingsController::getGst() }}">
                </div>
            </div>

            <div class="field">
                <label class="label">PST<sup style="color: red">*</sup></label>
                <div class="control has-icons-left">
                    <span class="icon is-small is-left">
                        <i class="fas fa-percent"></i>
                    </span>
                    <input type="number" step="0.01" name="pst" class="input" value="{{ SettingsController::getPst() }}">
                </div>
            </div>

            <div class="field">
                <div class="control">
                    <label class="checkbox label" title="Allow Cashiers + Managers to ring up orders for themselves.">
                        Self Purchases
                        <input type="checkbox" name="self_purchases" @if(SettingsController::getSelfPurchases()) checked @endif>
                    </label>
                </div>
            </div>

            <br>
            
            <div class="control">
                <button class="button is-success" type="submit">
                    <span class="icon is-small">
                        <i class="fas fa-save"></i>
                    </span>
                    <span>Save</span>
                </button>
            </div>
        </form>
    </div>

    <div class="column"></div>

    <div class="column box is-4">
        <h4 class="title has-text-weight-bold is-4">Roles</h4>
        <table id="role_list">
            <thead>
                <tr>
                    <th>Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach(Roles::getRoles() as $role)
                    <tr>
                        <td>
                            <div>{{ ucfirst($role->name) }}</div>
                        </td>
                        <td>
                            <div>
                                <a href="javascript:;" data-toggle="modal" onclick="deleteData('{{ $role->name }}')" data-target="#DeleteModal">Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br>
        <a class="button is-success" onclick="window.location='settings/category/new';">
            <span class="icon is-small">
                <i class="fas fa-plus"></i>
            </span>
            <span>New</span>
        </a>
    </div>

    <div class="column"></div>

    <div class="column box is-3">
        <h4 class="title has-text-weight-bold is-4">Categories</h4>
        <table id="category_list">
            <thead>
                <tr>
                    <th>Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach(SettingsController::getCategories() as $category)
                <tr>
                    <td>
                        <div>{{ ucfirst($category->value) }}</div>
                    </td>
                    <td>
                        <div>
                            <form>
                                <input type="hidden" id="{{ $category->value }}" value="{{ $category->value }}">
                                <a href="javascript:;" data-toggle="modal"
                                    onclick="deleteData('{{ $category->value }}')" data-target="#DeleteModal">Delete</a>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <br>
        <a class="button is-success" onclick="window.location='settings/category/new';">
            <span class="icon is-small">
                <i class="fas fa-plus"></i>
            </span>
            <span>New</span>
        </a>
    </div>
</div>

<div id="DeleteModal" class="modal fade" role="dialog">
    <div class="modal-dialog ">
        <form action="" id="deleteForm" method="get">
            <div class="modal-content">
                <div class="modal-body">
                    @csrf
                    <p class="text-center">Are you sure you want to delete this category?</p>
                </div>
                <div class="modal-footer">
                    <center>
                        <button type="button" class="button is-success" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="" class="button is-success" data-dismiss="modal"
                            onclick="formSubmit()">Delete</button>
                    </center>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#role_list').DataTable({
            "order": [],
            "paging": false,
            "searching": false,
            "scrollY": "49vh",
            "scrollCollapse": true,
            "bInfo": false,
            "columnDefs": [
                { 
                    "orderable": false, 
                    "targets": 1
                }
            ]
        });
        $('#category_list').DataTable({
            "paging": false,
            "searching": false,
            "scrollY": "49vh",
            "scrollCollapse": true,
            "bInfo": false,
            "columnDefs": [
                { 
                    "orderable": false, 
                    "targets": 1
                }
            ]
        });
    });

    function deleteData(category_name) {
        let name = document.getElementById(category_name).value;
        let url = '{{ route("delete_category", ":name") }}';
        url = url.replace(':name', name);
        $("#deleteForm").attr('action', url);
    }

    function formSubmit() {
        $("#deleteForm").submit();
    }
</script>
@stop