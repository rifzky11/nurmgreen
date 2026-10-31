@php
    // Partial ini dipakai untuk create (tanpa $category) dan edit (dengan $category)
    $isEdit = isset($category);
@endphp

<form action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      method="POST" novalidate>
    @csrf
    @if ($isEdit)
        {{-- HTML form hanya kenal GET/POST, jadi method PUT dikirim lewat hidden _method --}}
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="name">Nama Kategori</label>
        <input type="text" id="name" name="name" class="form-control"
               value="{{ old('name', $category->name ?? '') }}" maxlength="255" required autofocus>
        @error('name')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="description">Deskripsi <span class="muted">(opsional)</span></label>
        <textarea id="description" name="description" class="form-control" rows="4"
                  maxlength="1000">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <p class="form-hint">
        Slug dibuat otomatis dari nama saat disimpan.
        @if ($isEdit)
            Slug sekarang: <code>{{ $category->slug }}</code>
        @endif
    </p>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">
            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Kategori' }}
        </button>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>
