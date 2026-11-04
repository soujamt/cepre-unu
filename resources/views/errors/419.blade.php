@extends ('errors::layout')

@section ('code', '419')
@section ('title', 'Tiempo de espera agotado')

@section ('illustration')
    <x-ui.icon name="clock" size="10" />
@endsection

@section ('message')
    No tienes los permisos necesarios para acceder a este recurso. Si crees que esto es un error, contacta a soporte
    técnico.
@endsection
