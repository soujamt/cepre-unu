@extends ('errors::layout')

@section ('code', '403')
@section ('title', 'Acceso Denegado')

@section ('illustration')
    <x-ui.icon name="lock" size="10" />
@endsection

@section ('message')
    No tienes los permisos necesarios para acceder a este recurso. {{ $exception->getMessage() ?? '' }}
@endsection
