@extends('layouts.app')


@section('content')
    <div class="container mt-4">
        <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">Kembali ke Daftar Folder</a>

        <h2>Isi File ZIP: {{ $file->file_name }}</h2>

        

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($fileContents))
            <ul class="list-group">
                @foreach ($fileContents as $content)
                    <li class="list-group-item">
                        <strong>{{ $content['name'] }}</strong>
                        <div>
                            <!-- Menampilkan file PDF -->
                            @if (strpos($content['name'], '.pdf') !== false)
                                <iframe src="data:application/pdf;base64,{{ $content['content'] }}" width="100%"
                                    height="600px"></iframe>
                                <!-- Menampilkan gambar jika konten adalah gambar -->
                            @elseif (strpos($content['content'], 'data:image') === 0)
                                {!! $content['content'] !!}
                                <!-- Menampilkan teks biasa jika konten adalah teks -->
                            @else
                                <pre>{!! $content['content'] !!}</pre>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p>File ZIP ini kosong atau tidak memiliki file yang dapat ditampilkan.</p>
        @endif

        <a href="{{ url()->previous() }}" class="btn btn-secondary mb-3">Kembali ke Daftar Folder</a>
    </div>
@endsection
