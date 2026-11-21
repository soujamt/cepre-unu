@extends ('errors::layout')

@section ('code', '401')
@section ('title', 'No Autorizado')

@section ('icono', 'candado')

@section ('message')
    No estás autorizado para acceder a este recurso. Por favor, inicia sesión con las credenciales correctas o contacta
    a soporte técnico si el problema persiste.
@endsection
