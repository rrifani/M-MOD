@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2>Dashboard</h2>
    <p>Selamat datang di dashboard.</p>
    @if (auth()->user()->role === 'admin')
        <a href="{{ url('/admin/files') }}" class="btn btn-primary">Kelola File</a>
    @elseif (auth()->user()->role === 'user')
        <a href="{{ url('/files') }}" class="btn btn-primary">Unggah dan Lihat File</a>
    @endif
</div>
@endsection
