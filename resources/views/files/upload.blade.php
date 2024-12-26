@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <!-- Tombol Kembali -->
    <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">Kembali</a>
    <h2>Unggah File Baru</h2>

    <!-- Tampilkan pesan kesalahan jika ada -->
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Menampilkan formulir unggah untuk Admin -->
    @if(auth()->user()->role === 'admin')
        <form action="{{ route('admin.files.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Input File Multiple -->
            <div class="mb-3">
                <label for="file" class="form-label">Pilih File</label>
                <input type="file" class="form-control" id="file" name="files[]" multiple required>
                <small class="text-muted">Anda dapat memilih beberapa file sekaligus.</small>
            </div>

            <!-- Input untuk Nama Folder ZIP -->
            <div class="mb-3">
                <label for="zip_folder_name" class="form-label">Nama Folder ZIP</label>
                <input type="text" class="form-control" id="zip_folder_name" name="zip_folder_name" placeholder="Masukkan nama folder ZIP" required>
                <small class="text-muted">Nama folder ZIP untuk menyimpan file yang diunggah.</small>
            </div>

            <!-- Dropdown Kategori -->
            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select name="category_id" id="category_id" class="form-control" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Pilih kategori untuk file ini.</small>
            </div>

            <!-- Pencarian Folder dan Pilihan Folder -->
            <div class="mb-3">
                <label for="folder_search" class="form-label">Cari Folder</label>
                <input type="text" id="folder_search" class="form-control" placeholder="Cari folder...">
            </div>

            <div class="mb-3">
                <label for="folder_name" class="form-label">Pilih Folder</label>
                <select name="folder_name" id="folder_name" class="form-control" required>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder }}">{{ $folder }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Unggah</button>
        </form>
    @endif

    <!-- Menampilkan formulir unggah untuk User -->
    @if(auth()->user()->role === 'user')
        <form action="{{ route('user.files.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Input File Multiple -->
            <div class="mb-3">
                <label for="file" class="form-label">Pilih File</label>
                <input type="file" class="form-control" id="file" name="files[]" multiple required>
                <small class="text-muted">Anda dapat memilih beberapa file sekaligus.</small>
            </div>

            <!-- Input untuk Nama Folder ZIP -->
            <div class="mb-3">
                <label for="zip_folder_name" class="form-label">Nama Folder ZIP</label>
                <input type="text" class="form-control" id="zip_folder_name" name="zip_folder_name" placeholder="Masukkan nama folder ZIP" required>
                <small class="text-muted">Nama folder ZIP untuk menyimpan file yang diunggah.</small>
            </div>

            <!-- Dropdown Kategori untuk User -->
            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select name="category_id" id="category_id" class="form-control" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Pilih kategori untuk file ini.</small>
            </div>

            <!-- Pencarian Folder dan Pilihan Folder untuk User -->
            <div class="mb-3">
                <label for="folder_search" class="form-label">Cari Folder</label>
                <input type="text" id="folder_search" class="form-control" placeholder="Cari folder...">
            </div>

            <div class="mb-3">
                <label for="folder_name" class="form-label">Pilih Folder</label>
                <select name="folder_name" id="folder_name" class="form-control" required>
                    @foreach ($folders as $folder)
                        <option value="{{ $folder }}">{{ $folder }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" id="uploadButton" class="btn btn-primary">Unggah</button>

        </form>
    @endif
</div>

<script>
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            const uploadButton = form.querySelector('button[type="submit"]');
            uploadButton.disabled = true; // Nonaktifkan tombol unggah
            uploadButton.innerText = 'Mengunggah...'; // Ubah teks tombol
        });
    });

    // Script untuk pencarian folder
    document.getElementById('folder_search').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const options = document.getElementById('folder_name').options;

        for (let i = 0; i < options.length; i++) {
            const optionText = options[i].text.toLowerCase();
            options[i].style.display = optionText.includes(searchValue) ? '' : 'none';
        }
    });
</script>

@endsection
