@extends('layouts.app')

@section('styles')
<!-- Tempatkan CSS khusus di sini jika diperlukan -->
<style>
    .dropdown-content {
        display: none;
        animation: fadeIn 0.3s ease-in-out;
    }

    .folder-item {
        margin-left: 20px;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
@endsection

@section('content')
<div class="container">
    <!-- Tombol Kembali -->
    <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">Kembali ke Daftar Folder</a>

    <h1>Daftar Folder</h1>

    <!-- Form Pencarian Folder -->
    <form method="GET" action="{{ route('folders.index') }}">
        <input type="text" name="search" value="{{ request()->input('search') }}" class="form-control" placeholder="Cari folder..." />
        <button type="submit" class="btn btn-primary mt-2">Cari</button>
    </form>

    @if($paginatedFolders->count() > 0)
        <ul class="list-group mt-3" id="folderList">
            @foreach($paginatedFolders as $folder)
                <li class="list-group-item folder-item" data-folder-name="{{ $folder['name'] }}">
                    <a href="javascript:void(0);" class="folder-name" data-folder="{{ $folder['name'] }}">
                        {{ $folder['name'] }}
                    </a>
                    
                    <!-- Menampilkan subfolder -->
                    @if(count($folder['subfolders']) > 0)
                        <div class="dropdown-content collapse" id="dropdown-folder-{{ str_replace(' ', '_', $folder['name']) }}">
                            <ul class="list-group">
                                @foreach($folder['subfolders'] as $subfolder)
                                    <li class="list-group-item" data-subfolder-name="{{ $subfolder['name'] }}">
                                        <a href="javascript:void(0);" class="folder-name" data-folder="{{ $subfolder['name'] }}">
                                            {{ $subfolder['name'] }}
                                        </a>

                                        <!-- Menampilkan file ZIP dalam subfolder -->
                                        <div class="dropdown-content collapse" id="dropdown-subfolder-{{ str_replace(' ', '_', $folder['name']) }}-{{ str_replace(' ', '_', $subfolder['name']) }}">
                                            @php
                                                $zipFiles = Storage::disk('public')->files($subfolder['path']);
                                            @endphp

                                            @if(count($zipFiles) > 0)
                                                <ul class="list-group">
                                                    @foreach($zipFiles as $file)
                                                    @php
                                                        // Ambil file path dan nama file
                                                        $filePath = $file;
                                                        $fileName = basename($file);
                                                
                                                        // Cari berdasarkan path lengkap di database
                                                        $fileObj = App\Models\File::where('file_path', $filePath)->first();
                                                    @endphp
                                                    @if($fileObj)
                                                        <li class="list-group-item">
                                                            <a href="{{ auth()->user()->role === 'admin' ? route('admin.files.showContents', ['file' => $fileObj->id]) : route('user.files.showContents', ['file' => $fileObj->id]) }}">
                                                                {{ $fileName }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                                
                                                </ul>
                                            @else
                                                <p class="text-muted">Tidak ada file ZIP dalam subfolder ini.</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>

        <!-- Link Pagination -->
        <div class="d-flex justify-content-center mt-3">
            {{ $paginatedFolders->appends(['search' => request()->input('search')])->links() }}
        </div>
    @else
        <p>Tidak ada folder yang ditemukan.</p>
    @endif
</div>

<script>
    // JavaScript untuk menangani klik pada folder dan menampilkan dropdown
    document.querySelectorAll('.folder-name[data-folder]').forEach(function(folderLink) {
        folderLink.addEventListener('click', function() {
            const folderName = folderLink.getAttribute('data-folder');
            const dropdown = document.getElementById('dropdown-folder-' + folderName.replace(' ', '_'));
            
            // Toggle visibility of the folder dropdown
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
    });

    // JavaScript untuk menangani klik pada subfolder dan menampilkan dropdown ZIP
    document.querySelectorAll('.folder-name[data-folder]').forEach(function(subfolderLink) {
        subfolderLink.addEventListener('click', function() {
            const folderName = subfolderLink.closest('.folder-item').getAttribute('data-folder-name');
            const subfolderName = subfolderLink.getAttribute('data-folder');
            const dropdown = document.getElementById('dropdown-subfolder-' + folderName.replace(' ', '_') + '-' + subfolderName.replace(' ', '_'));
            
            // Toggle visibility of the subfolder dropdown
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
    });

    // JavaScript untuk fitur pencarian folder
    const searchInput = document.querySelector('input[name="search"]');
    searchInput.addEventListener('input', function() {
        const searchValue = searchInput.value.toLowerCase();

        // Update URL dengan parameter pencarian
        const url = new URL(window.location.href);
        if (searchValue) {
            url.searchParams.set('search', searchValue); // Set parameter pencarian
        } else {
            url.searchParams.delete('search'); // Hapus jika tidak ada pencarian
        }
        window.history.pushState({}, '', url); // Memperbarui URL tanpa reload halaman

        // Menampilkan atau menyembunyikan folder berdasarkan pencarian
        const folders = document.querySelectorAll('.folder-item');
        folders.forEach(function(folder) {
            const folderName = folder.getAttribute('data-folder-name').toLowerCase();
            if (folderName.indexOf(searchValue) !== -1) {
                folder.style.display = '';
            } else {
                folder.style.display = 'none';
            }
        });
    });
</script>
@endsection
