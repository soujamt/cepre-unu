@extends ('errors::layout')

@section ('code', '419')
@section ('title', 'Tiempo de espera agotado')

@section ('illustration')
    <i class="ki-duotone ki-time text-7xl text-red-600"></i>
@endsection

@section ('message')
    No tienes los permisos necesarios para acceder a este recurso. Si crees que esto es un error, contacta a soporte
    técnico.
@endsection
