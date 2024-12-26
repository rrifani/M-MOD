@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <h2>Daftar File dan Folder</h2>

        <!-- Pesan Sukses atau Error -->
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <!-- Opsi untuk Admin -->
        @if (auth()->user()->role === 'admin')
            <!-- Form Pembuatan Folder -->
            <form action="{{ route('admin.folder.create') }}" method="POST" class="mb-4">
                @csrf
                <div class="form-group">
                    <label for="parent_folder">Pilih Folder Induk</label>
                    <select name="parent_folder" id="parent_folder" class="form-control">
                        <option value="">Tanpa Induk</option>
                        @foreach ($folders as $folder)
                            @php
                                $level = substr_count($folder, '/');
                            @endphp
                            <option value="{{ $folder }}">{!! str_repeat('--', $level) !!} {{ basename($folder) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="folder_name">Nama Folder</label>
                    <input type="text" name="folder_name" class="form-control" placeholder="Nama Folder Baru" required>
                </div>
                <button type="submit" class="btn btn-primary">Buat Folder</button>
            </form>

            <a href="{{ route('admin.files.upload') }}" class="btn btn-primary mb-3">Unggah File Baru</a>
        @elseif (auth()->user()->role === 'user')
            <a href="{{ route('user.files.upload') }}" class="btn btn-primary mb-3">Unggah File Baru</a>
        @endif

        <!-- Filter Kategori -->
        <form method="GET"
            action="{{ request()->routeIs('admin.*') ? route('admin.files.index') : route('user.files.index') }}"
            class="mb-4">
            <div class="form-group">
                <label for="category_id">Pilih Kategori</label>
                <select name="category_id" id="category_id" class="form-control">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ request()->category_id == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <!-- Pencarian Folder dan File -->
        <div class="mb-3">
            <input type="text" id="search" class="form-control" placeholder="Cari Folder atau File...">
        </div>

      <!-- Daftar Folder -->
@if (!empty($folders))
<h4>Daftar Folder:</h4>
<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
    <ul id="folder-list" class="list-unstyled">
        @foreach ($folders as $folder)
            <li>
                {{ basename($folder) }}
                 <!-- Form Hapus Folder Induk -->
                 @if (auth()->user()->role === 'admin')
                 <form action="{{ route('admin.folder.delete') }}" method="POST" class="delete-folder-form" style="display:inline;">
                     @csrf
                     <input type="hidden" name="folder_name" value="{{ $folder }}">
                     <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                 </form>
             @endif
                @php
                $subfolders = Storage::disk('public')->directories($folder);
            @endphp
            
            @if (!empty($subfolders))
                <ul>
                    @foreach ($subfolders as $subfolder)
                        <li>
                            {{ basename($subfolder) }}
                            
                            @if (auth()->user()->role === 'admin')
                                <!-- Tombol Hapus Subfolder -->
                                <form action="{{ route('admin.folder.delete') }}" method="POST" class="delete-folder-form" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="folder_name" value="{{ $subfolder }}">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            @endif
            
                            <!-- Sub-Subfolder -->
                            @php
                                $subsubfolders = Storage::disk('public')->directories($subfolder);
                            @endphp
                            @if (!empty($subsubfolders))
                                <ul>
                                    @foreach ($subsubfolders as $subsubfolder)
                                        <li>
                                            {{ basename($subsubfolder) }}
            
                                            @if (auth()->user()->role === 'admin')
                                                <!-- Tombol Hapus Sub-Subfolder -->
                                                <form action="{{ route('admin.folder.delete') }}" method="POST" class="delete-folder-form" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="folder_name" value="{{ $subsubfolder }}">
                                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            
            </li>
        @endforeach
    </ul>
</div>
@endif


        <!-- Daftar File -->
        @if ($files->isEmpty())
            <p>Tidak ada file yang diunggah.</p>
        @else
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Nama File</th>
                            <th>Ukuran (KB)</th>
                            <th>Folder</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="file-list">
                        @foreach ($files as $file)
                            <tr>
                                <td>{{ $file->file_name }}</td>
                                <td>{{ number_format($file->file_size / 1024, 2) }}</td>
                                <td>{{ $file->folder_name }}</td>
                                <td>{{ $file->category ? $file->category->name : 'Tidak ada kategori' }}</td>
                                <td>
                                    <a href="{{ route(auth()->user()->role . '.files.showContents', $file->id) }}"
                                        class="btn btn-info btn-sm">Lihat Isi ZIP</a>
                                    <a href="{{ route(auth()->user()->role . '.files.download', $file->id) }}"
                                        class="btn btn-success btn-sm">Unduh</a>
                                    @if (auth()->user()->role === 'admin')
                                        <form action="{{ route('admin.files.destroy', $file->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Script untuk Pencarian -->
    <script>
        document.querySelectorAll('form.d-inline button[type="submit"]').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Apakah Anda yakin ingin menghapus file ini?')) {
                    e.preventDefault();
                }
            });
        });



        document.getElementById('search').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();

            // Filter folder
            const folders = document.getElementById('folder-list').getElementsByTagName('li');
            Array.from(folders).forEach(folder => {
                const folderName = folder.textContent.toLowerCase();
                folder.style.display = folderName.includes(searchValue) ? '' : 'none';
            });

            // Filter file
            const files = document.getElementById('file-list').getElementsByTagName('tr');
            Array.from(files).forEach(file => {
                const fileName = file.getElementsByTagName('td')[0].textContent.toLowerCase();
                const folderName = file.getElementsByTagName('td')[2].textContent.toLowerCase();
                file.style.display = fileName.includes(searchValue) || folderName.includes(searchValue) ?
                    '' : 'none';
            });
        });

         document.querySelectorAll('.delete-folder-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus folder ini? Penghapusan folder tidak dapat dibatalkan.')) {
                e.preventDefault();
            }
        });
    });
    </script>
@endsection
