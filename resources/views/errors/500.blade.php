@extends ('errors::layout')

@section ('code', '500')
@section ('title', 'Error del servidor')

@section ('illustration')
    <x-ui.icon name="info-circle" size="10" />
@endsection

@section ('message')
    Ha ocurrido un error interno en el servidor. Por favor, intenta nuevamente más tarde o contacta a soporte técnico si
    el problema persiste.
@endsection
