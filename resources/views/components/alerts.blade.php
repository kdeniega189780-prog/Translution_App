@if (session('status'))
    <div class="alert success" role="status">
        <x-icon name="check" />{{ session('status') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert danger" role="alert">
        <div><strong>Please check the following:</strong>
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
