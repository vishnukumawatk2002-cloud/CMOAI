@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-control' . ($errors->has($attributes->get('name')) ? ' is-invalid' : '')]) }}>
