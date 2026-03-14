@extends ('errors::layout')

@section ('code', '403')
@section ('title', 'Acceso Denegado')

@section ('illustration')
    <i class="ki-duotone ki-lock text-7xl text-red-600"></i>
@endsection

@section ('message')
    No tienes los permisos necesarios para acceder a este recurso. {{ $exception->getMessage() ?? '' }}
@endsection
