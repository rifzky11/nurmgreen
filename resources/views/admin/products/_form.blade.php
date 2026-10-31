@php
    // Partial ini dipakai create (tanpa $product) dan edit (dengan $product)
    $isEdit = isset($product);
@endphp

<form action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}"
      method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($categories->isEmpty())
        <p class="form-error">
            Belum ada kategori. <a href="{{ route('admin.categories.create') }}">Buat kategori dulu</a>
            sebelum menambah produk.
        </p>
    @endif

    <div class="form-group">
        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id" class="form-control" required>
            <option value="">— Pilih kategori —</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    @selected((string) old('category_id', $product->category_id ?? '') === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="name">Nama Produk</label>
        <input type="text" id="name" name="name" class="form-control"
               value="{{ old('name', $product->name ?? '') }}" maxlength="255" required>
        @error('name')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" class="form-control" rows="5"
                  maxlength="5000" required>{{ old('description', $product->description ?? '') }}</textarea>
        @error('description')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="price">Harga (Rp)</label>
            <input type="number" id="price" name="price" class="form-control"
                   value="{{ old('price', $product->price ?? '') }}"
                   min="0" step="0.01" required>
            @error('price')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="stock">Stok</label>
            <input type="number" id="stock" name="stock" class="form-control"
                   value="{{ old('stock', $product->stock ?? 0) }}"
                   min="0" step="1" required>
            @error('stock')
                <span class="form-error">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <label for="image">Gambar Produk <span class="muted">(opsional)</span></label>
        <input type="file" id="image" name="image" class="form-control"
               accept=".jpg,.jpeg,.png,.webp">
        <span class="form-hint">
            JPG/JPEG, PNG, atau WEBP. Maksimal 2 MB.
            Batas upload server saat ini: {{ ini_get('upload_max_filesize') }}.
            Jika sebelumnya muncul error validasi, <strong>pilih ulang file</strong> —
            browser mengosongkan input file setiap halaman dimuat ulang.
        </span>
        @error('image')
            <span class="form-error">{{ $message }}</span>
        @enderror

        @if ($isEdit && $product->image)
            <div class="current-image">
                <img src="{{ asset('storage/'.$product->image) }}" alt="{{ $product->name }}">
                <span class="muted">Gambar saat ini. Unggah file baru untuk menggantinya.</span>
            </div>
        @endif
    </div>

    <div class="form-group">
        <label class="checkbox-row">
            {{-- Hidden 0 memastikan nilai tetap terkirim saat checkbox tidak dicentang --}}
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1"
                @checked(old('is_active', $product->is_active ?? true))>
            Produk aktif (tampil di katalog)
        </label>
        @error('is_active')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-solid">
            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' }}
        </button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Batal</a>
    </div>
</form>
