{{-- Form alamat dipakai untuk tambah (POST) dan ubah (PUT) --}}
<form action="{{ $action }}" method="POST" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="form-group">
        <label for="label">Label Alamat</label>
        <input type="text" id="label" name="label" class="form-control"
               value="{{ old('label', $address?->label) }}" maxlength="50"
               placeholder="Contoh: Rumah, Kantor" required>
        @error('label')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="recipient_name">Nama Penerima</label>
        <input type="text" id="recipient_name" name="recipient_name" class="form-control"
               value="{{ old('recipient_name', $address?->recipient_name) }}" maxlength="100" required>
        @error('recipient_name')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="phone">Nomor HP</label>
        <input type="tel" id="phone" name="phone" class="form-control"
               value="{{ old('phone', $address?->phone) }}" maxlength="20"
               placeholder="08xxxxxxxxxx" required>
        @error('phone')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="address">Alamat Lengkap</label>
        <textarea id="address" name="address" class="form-control" rows="3"
                  maxlength="500" required>{{ old('address', $address?->address) }}</textarea>
        @error('address')
            <span class="form-error">{{ $message }}</span>
        @enderror
    </div>

    <button type="submit" class="btn btn-solid">{{ $submitLabel }}</button>
</form>
