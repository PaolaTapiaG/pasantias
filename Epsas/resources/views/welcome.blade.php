@extends('layouts.app')

@section('title', 'Bienvenido a EPSAS')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-5xl font-bold text-gray-900 mb-4">EPSAS</h1>
        <p class="text-xl text-gray-600 mb-8">Sistema de Gestión de Agua Potable</p>
        
        @auth
            <p class="text-lg text-gray-700 mb-6">¡Bienvenido {{ Auth::user()->name }}!</p>
            <a href="{{ route('dashboard') }}" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                Ir al Dashboard
            </a>
        @else
            <p class="text-lg text-gray-600 mb-8">Por favor, inicia sesión para continuar</p>
            <a href="{{ route('login') }}" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
                Iniciar Sesión
            </a>
        @endauth
    </div>
</div>
@endsection