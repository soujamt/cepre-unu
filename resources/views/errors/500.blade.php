@extends ('errors::layout')

@section ('code', '500')
@section ('title', 'Error del servidor')

@section ('icono', 'alerta')

@section ('message')
    Ha ocurrido un error interno en el servidor. Por favor, intenta nuevamente más tarde o contacta a soporte técnico si
    el problema persiste.
@endsection
