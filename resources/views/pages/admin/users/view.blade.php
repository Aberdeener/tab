@extends('layouts.default', ['page' => 'users'])
@section('content')
<h2 class="title has-text-weight-bold">View User</h2>
<h4 class="subtitle">
    {{ $user->full_name }} @if(hasPermission(\App\Helpers\Permission::USERS_MANAGE) && auth()->user()->role->canInteract($user->role))<a href="{{ route('users_edit', $user->id) }}">(Edit)</a>@endif
</h4>

@canImpersonate
    @canBeImpersonated($user)
        <a href="{{ route('impersonate', $user) }}" class="button is-light">
            🕵 Impersonate
        </a>
        <br />
        <br />
    @endCanBeImpersonated
@endCanImpersonate

<div class="box">
    <nav class="level">
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Balance</p>
                <p class="title">{{ $user->balance }}</p>
            </div>
        </div>
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Total spent</p>
                <p class="title">{{ $user->findSpent() }}</p>
            </div>
        </div>
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Total returned</p>
                <p class="title">{{ $user->findReturned() }}</p>
            </div>
        </div>
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Total paid out</p>
                <p class="title">{{ $user->findPaidOut() }}</p>
            </div>
        </div>
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Total owing</p>
                <a class="title" title="View PDF" style="text-decoration: underline;" href="{{ route('users_pdf', $user) }}" target="_blank">{{ $user->findOwing() }}</a>
            </div>
        </div>
    </nav>
</div>

<div class="columns">
    <div class="column">
        <div class="columns is-multiline">
            @if($user->family)
                <div class="column is-full">
                    <x-detail-card title="Family">
                        <p><strong>Name:</strong> {{ $user->family->name }}</p>
                        <p><strong>Role:</strong> {{ $user->familyRole() }}</p>
                    </x-detail-card>
                </div>
            @endif
            <div class="column">
                <livewire:common.users.orders-list :user="$user" context="admin" />
            </div>
            <div class="column is-full">
                <livewire:common.users.activity-registrations-list :user="$user" context="admin" />
            </div>
            <div class="column">
                <x-entity-timeline :timeline="$user->timeline()" />
            </div>
        </div>
    </div>

    <div class="column">
        <div class="columns is-multiline">
            <div class="column">
                <livewire:common.users.category-limits-list :user="$user" />
            </div>
            <div class="column">
                <livewire:admin.users.rotations-list :user="$user" />
            </div>
            <div class="column is-full">
                <livewire:common.users.payouts-list :user="$user" context="admin" />
            </div>
        </div>
    </div>
</div>
@endsection
