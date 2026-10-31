@extends('layouts.app')

@section('title', 'Kelola Produk')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Kelola Produk</h2>
            <a href="{{ route('admin.products.create') }}" class="btn btn-solid">+ Tambah Produk</a>
        </div>

        {{-- Filter memakai GET supaya bisa dibagikan lewat URL --}}
        <form action="{{ route('admin.products.index') }}" method="GET" class="filter-bar">
            <input type="search" name="q" value="{{ $search }}" class="form-control"
                   placeholder="Cari nama produk..." aria-label="Cari nama produk">

            <select name="category" class="form-control" aria-label="Filter kategori">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-solid">Cari</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline">Reset</a>
        </form>

        <div class="panel">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:70px">Gambar</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th style="width:120px">Harga</th>
                        <th style="width:70px">Stok</th>
                        <th style="width:90px">Status</th>
                        <th style="width:190px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                @if ($product->image)
                                    <img src="{{ asset('storage/'.$product->image) }}"
                                         alt="{{ $product->name }}" class="thumb">
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $product->name }}</strong>
                                <div class="muted"><code>{{ $product->slug }}</code></div>
                            </td>
                            <td>{{ $product->category->name }}</td>
                            <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td>{{ $product->stock }}</td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge badge-on">Aktif</span>
                                @else
                                    <span class="badge badge-off">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('admin.products.show', $product) }}"
                                       class="btn btn-outline">Detail</a>
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                       class="btn btn-outline">Edit</a>

                                    <form action="{{ route('admin.products.destroy', $product) }}"
                                          method="POST"
                                          onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted">
                                Tidak ada produk yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $products->links('pagination.simple-green') }}
        </div>
    </div>
@endsection
