@extends ('errors::layout')

@section ('code', '503')
@section ('title', 'Servicio no disponible')

@section ('illustration')
    <x-ui.icon name="x-circle" size="10" />
@endsection

@section ('message')
    El servicio no está disponible en este momento. Por favor, intenta nuevamente más tarde o contacta a soporte técnico
    si el problema persiste.
@endsection
