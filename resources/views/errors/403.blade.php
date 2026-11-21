@extends ('errors::layout')

@section ('code', '403')
@section ('title', 'Acceso Denegado')

@section ('icono', 'candado')

@section ('message')
    No tienes los permisos necesarios para acceder a este recurso. {{ $exception->getMessage() ?? '' }}
@endsection
