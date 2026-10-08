@extends('layouts.app')
@section('title', 'Services & Prices')
@section('content')<div class="page-heading">
        <div><span class="eyebrow">CLEAR PRICES. CONFIDENT CARE.</span>
            <h1>Services & prices</h1>
            <p>Set starting prices. Adjust a job’s estimate after diagnosis when needed.</p>
        </div><a class="button primary" href="{{ route('services.create') }}"><x-icon name="plus" />Add service</a>
    </div>
    <section class="card" id="services-list">
        {{ $services->links('pagination.simple', ['label' => 'Services']) }}
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Starting price</th>
                        <th>Available</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <td><strong>{{ $service->name }}</strong></td>
                            <td>₱{{ number_format($service->price, 2) }}</td>
                            <td>{{ $service->active ? 'Yes' : 'No' }}</td>
                            <td><a class="button compact" href="{{ route('services.edit', $service) }}">Edit price</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $services->links('pagination.simple', ['label' => 'Services']) }}
</section>@endsection
