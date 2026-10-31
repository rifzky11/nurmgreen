@extends('layouts.app')

@section('title', 'Kelola Kategori')

@section('content')
    <div class="section">
        <div class="section-head">
            <h2>Kelola Kategori</h2>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-solid">+ Tambah Kategori</a>
        </div>

        <div class="panel">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Nama</th>
                        <th>Slug</th>
                        <th>Deskripsi</th>
                        <th style="width:90px">Produk</th>
                        <th style="width:170px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            {{-- Nomor urut mengikuti halaman, bukan id database --}}
                            <td>{{ $categories->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td><code>{{ $category->slug }}</code></td>
                            <td>
                                {{ $category->description
                                    ? \Illuminate\Support\Str::limit($category->description, 60)
                                    : '—' }}
                            </td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                       class="btn btn-outline">Edit</a>

                                    {{-- Hapus harus POST + method DELETE + CSRF --}}
                                    <form action="{{ route('admin.categories.destroy', $category) }}"
                                          method="POST"
                                          onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Belum ada kategori. Silakan tambah kategori pertama.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $categories->links('pagination.simple-green') }}
        </div>
    </div>
@endsection
