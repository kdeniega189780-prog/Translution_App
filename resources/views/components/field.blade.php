@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => true, 'hint' => null])
<div class="field">
    <label for="{{ $name }}">{{ $label }} @unless ($required)
            <span class="muted">(optional)</span>
        @endunless
    </label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" @required($required) {{ $attributes }}>{{ old($name, $value) }}</textarea>
    @else<div class="{{ $type === 'password' ? 'password-wrap' : '' }}"><input id="{{ $name }}"
                name="{{ $name }}" type="{{ $type }}"
                @if ($type !== 'password') value="{{ old($name, $value) }}" @endif @required($required)
                {{ $attributes }}>
            @if ($type === 'password')
                <button type="button" class="password-toggle" data-password="{{ $name }}"
                    aria-label="Show {{ $label }}" aria-pressed="false"><x-icon name="eye" /></button>
            @endif
        </div>
    @endif
    @if ($hint)
        <small>{{ $hint }}</small>
    @endif
    @error($name)
        <small class="error">{{ $message }}</small>
    @enderror
</div>
